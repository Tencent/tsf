<?php

/**
 * 将json转成二进制格式的编码解码
 * 支持yaaf框架的编码
 * 
 * author: seanfan
 * date: 2014/11/21
 */

class JsonManager {

    public function int_to_binary(&$str, $value) {
        if($value >= 0 && $value < 0xFF) {
            $str .= pack('cc', 0, $value);
        } else if($value >= 0xFF && $value <= 0xFFFF) {
            $str .= pack('cn', 1, $value);
        } else if($value > 0xFFFF && $value <= 0xFFFFFFFF) {
            $str .= pack('cN', 2, $value);
        } else if($value >=-2147483647 && $value<0){
            $str .= pack('cN', 2, $value);
        } else {
            $str .= pack('c', 3);
            $tmp1 = $value >> 32;
            $str .= pack('N', $tmp1);
            $tmp2 = $value << 32;
            $tmp3 = $tmp2 >> 32;
            $str .= pack('N', $tmp3);
        }
    }

    public function str_to_binary(&$str, $value) {
        $size = strlen($value);
        //echo "str:$value ,size:$size\n";
        if($size <= 0xFF) {
            $str .= pack('cC', 4, $size);
            $str .= $value;
        } else if ($size  <= 0xFFFF) {
            $str .= pack('cn', 5, $size);
            $str .= $value;
        } else {
            $str .= pack('cN', 6, $size);
            $str.=  $value;
        }
    }

    public function write_value(&$str, $value) {
        if(is_int($value) || is_float($value) || is_bool($value)) {
            $this->int_to_binary($str, $value);
        } else if(is_string($value)) {
            $this->str_to_binary($str, $value);
        } else if(is_array($value)) {
            $str .= pack('c', 7);
            $list_size = count($value);
            $str .= pack('N', $list_size);
            foreach ($value as $item) {
                $this->write_value($str, $item);
            }
        } else if(is_object($value)) {
            $str .= pack('c', 8);
            $obj_count = count((array)$value);
            $str .= pack('N', $obj_count);
            foreach ($value as $key => $item) {
                $key_len = strlen($key);
                $str .= pack('C', $key_len);
                $str .= $key;
                $this->write_value($str, $item);
            }

        } else {
            return;
        }
    }

    public function translate_to_obj($arr) {
        $str = json_encode($arr);
        return json_decode($str);
    }

    public function to_binary($root) {
        if(is_array($root)) {
            $data = $this->translate_to_obj($root);
            //error_log(__METHOD__.print_r($data,true),3,'/tmp/MenuModel.log');
        } else if(is_object($root)){
            $data = $root;
        } else {
            return '';
        }
        $str = '';
        $str .= pack('c', 0);
        $this->write_value($str, $data);
        return $str;
    }

    public function get_type($type_str) {
        $type_str_arr = unpack('ctmp', $type_str);
        if(!$type_str_arr || !isset($type_str_arr['tmp'])) {
            return -1;
        }
        return $type_str_arr['tmp'];
    }

    public function get_count($count_str) {
        $count_str_arr = unpack('Ntmp', $count_str);
        if(!$count_str_arr) {
            return -1;
        }
        $count = $count_str_arr['tmp'];
        return $count;
    }

    public function to_array($value) {
        $end = strlen($value);
        $start = 0;
        $result = array();
        if($end < 6) {
            return $result;
        }

        $type_str = $value[$start];
        $type = $this->get_type($type_str);
        if($type == -1) {
            return $result;
        }
        if($type == 0x00) {
            $start++;
        }

        $type_str = $value[$start];
        $type = $this->get_type($type_str);
        if($type == -1) {
            return $result;
        }
        if($type== 0x08) { //map
            $start++;
            $this->map_to_array($value, $start, $end, $result);
            return $result;
        }

        if($type == 0x07) { //list
            $start++;
            $this->list_to_array($value, $start, $end, $result);
            return $result;
        } 

        return $result;
    }

    public function map_to_array($value, $start, $end, &$result) {
        if($start == -1) {
            return -1;
        }
        if ($end-$start<4) {
            return -1;
        }
        $count_str = substr($value, $start, 4);
        $count = $this->get_count($count_str);
        $start += 4;
        if($count == -1) {
            return -1;
        }
        
        if($end - $start == 0) {
            return -1;
        }

        for ($i=0; $i < $count; $i++) { 
            $key_len_str = $value[$start];
            $start++;
            $key_len_arr = unpack('Ctmp', $key_len_str);
            if(!$key_len_arr) {
                return -1;
            }
            $key_len = $key_len_arr['tmp'];

            if($key_len <=0) {
                return -1;
            }

            $str_key = substr($value, $start, $key_len);
            $start += $key_len;

            $value_type_str = $value[$start];
            $value_type = $this->get_type($value_type_str);
            if($value_type == -1) {
                return -1;
            }

            if($value_type == 0x07) {
                $tmp_array = array();
                $start = $this->list_to_array($value, $start+1, $end, $tmp_array);
                $result[$str_key] = $tmp_array;
            } else if($value_type == 0x08) {
                $tmp_array = array();
                $start = $this->map_to_array($value, $start+1, $end, $tmp_array);
                $result[$str_key] = $tmp_array;
            } else {
                $nomal_value = $this->decode_nomal_type($value, $start, $end);
                $result[$str_key] = $nomal_value;
            }
            
        }

        return $start;
    }

    public function list_to_array($value, $start, $end, &$result) {
        if($start == -1) {
            return -1;
        }

        if ($end-$start<4) {
            return -1;
        }

        $count_str = substr($value, $start, 4);
        $count = $this->get_count($count_str);
        $start += 4;
        if($count == -1) {
            return -1;
        }

        for ($i=0; $i <$count ; $i++) { 
            $value_type_str = $value[$start];
            $value_type = $this->get_type($value_type_str);
            if($value_type == -1) {
                return -1;
            }

            if($value_type == 0x07) {
                $tmp_array = array();
                $start = $this->list_to_array($value, $start+1, $end, $tmp_array);
                $result[] = $tmp_array;
            } else if($value_type == 0x08) {
                $tmp_array = array();
                $start = $this->map_to_array($value, $start+1, $end, $tmp_array);
                $result[] = $tmp_array;
            } else {
                $nomal_value = $this->decode_nomal_type($value, $start, $end);
                $result[] = $nomal_value;
            }
        }

        return $start;

    }

    public function decode_nomal_type($value, &$start, $end) {
        $value_type_str = $value[$start];
        $value_type = $this->get_type($value_type_str);
        if($value_type == -1) {
            return -1;
        }
        $start++;

        if($value_type == 0) {

            $str = $value[$start];
            $str_arr = unpack('Ctmp', $str);
            $start++;
            return $str_arr['tmp'];

        } else if($value_type == 1) {

            $str = substr($value, $start, 2);
            $start += 2;
            $str_arr = unpack('ntmp', $str);
            return $str_arr['tmp'];
        } else if($value_type == 2) {
            $str = substr($value, $start, 4);
            $start += 4;
            $str_arr = unpack('Ntmp', $str);
            return $str_arr['tmp'];
        } else if($value_type == 3) {
            $str = substr($value, $start, 4);
            $start += 4;
            $begin_arr = unpack('Ntmp', $str);
            $begin = $begin_arr['tmp'];
            $str = substr($value, $start, 4);
            $start += 4;
            $end_arr = unpack('Ntmp', $str);
            $end = $end_arr['tmp'];
            $top = $begin << 32;
            return  $top + $end;
        } else if ($value_type == 4) {
            $str = substr($value, $start, 1);
            $start++;
            $str_len_arr = unpack('Ctmp', $str);
            $str_len = $str_len_arr['tmp'];
            if($str_len == 0) {
                $str_value = '';
            } else {
                $str_value = substr($value, $start, $str_len);
                $start += $str_len;
            }
            return $str_value;
        } else if($value_type == 5) {
            $str = substr($value, $start, 2);
            $start += 2;
            $str_len_arr = unpack('ntmp', $str);
            $str_len = $str_len_arr['tmp'];
            $str_value = substr($value, $start, $str_len);
            $start += $str_len;
            return $str_value;
        } else if($value_type == 6) {
            $str = substr($value, $start, 4);
            $start += 4;
            $str_len_arr = unpack('Ntmp', $str);
            $str_len = $str_len_arr['tmp'];
            $str_value = substr($value, $start, $str_len);
            $start += $str_len;
            return $str_value;
        } else {
            return -1;
        }
    }



}

//test
/*$json_manager = new JsonManager();
$str = '';

$test_a = '{
    "msg": {
        "item_attr": {
            "item_0": {
                "layout": 6
            }
        },
        "item": [
            [
                {
                    "title": "爆款稀缺手机年终低价巨献！"
                },
                {
                    "summary":"10月14日"
                },
                {
                    "picture": {},
                    "picture_attr": {
                        "cover": "http://pub.idqqimg.com/pc/mpqq/20141121/196.jpg"
                    }
                }
            ],
            [
                {
                    "hr": ""
                }
            ],
            [
                {
                    "more": ""
                }
            ]
        ],
        "source_attr": {
            "name": "",
            "icon": ""
        },
        "source": {}
    },
    "text": "",
    "msg_attr": {
        "actionData": "",
        "i_actionData": "",
        "brief": "爆款稀缺手机年终低价巨献！",
        "flag": 4,
        "url": "http://s.p.qq.com/pub/jump?url=http%3A%2F%2Fmm.wanggou.com%2Fpromote%2Fbrand_detail_shouq.shtml%3FactId%3D35287%26areaId%3D8777%26_wv%3D1%26PTAG%3D17006.3.3&k=2833910489&_wv=1",
        "serviceID": 0,
        "action": "web",
        "a_actionData": ""
    }
}';
$c = json_decode($test_a,TRUE);

//var_dump($c);

//$test_a = array("abcdsdadasds" => 0x0000000800000001, "b" => array("1",2,"3"), "c" => array("1"=>"2","2"=>"3", "4"), "d" => "http://s.p.qq.com/pub/jump?url=http%3A%2F%2Fmm.wanggou.com%2Fpromote%2Fhuichang.html%3F_wv%3D1%26ptag%3D17006.3.1&k=3809437297&_wv=1");
//$b = json_encode($test_a);
//$c = json_decode($b);
//$to_arr = $test_a;

//var_dump($c);
$to_arr = $c;

$str = $json_manager->to_binary($to_arr);
echo "$str\n";

$arr = $json_manager->to_array($str);
$json_str = json_encode($arr);
echo "$json_str\n";*/

?>