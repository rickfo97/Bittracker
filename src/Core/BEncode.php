<?php
namespace Rickfo97\Bittracker\Core;

class BEncode
{

    public static function build($input)
    {
        if (is_int($input)) {
            return self::makeInt($input);
        }
        if (is_string($input)) {
            return self::makeString($input);
        }
        if (is_array($input)) {
            if (self::isDictionary($input)) {
                return self::makeDictionary($input);
            } else {
                return self::makeList($input);
            }
        }
    }

    public static function decode($input, &$i = 0)
    {
        switch ($input[$i]){
            case 'd':
                $dictionary = array();
                while (isset($input[++$i])){
                    if($input[$i] == 'e'){
                        return $dictionary;
                    }else{
                        $key = self::decode($input, $i);
                        if(isset($input[++$i])){
                            $dictionary[$key] = self::decode($input, $i);
                        }
                    }
                }
            case 'l':
                $list = array();
                while (isset($input[++$i])){
                    if($input[$i] == 'e'){
                        return $list;
                    }else{
                        $list[] = self::decode($input, $i);
                    }
                }
            case 'i':
                $intBuffer = "";
                while (isset($input[++$i])){
                    if($input[$i] == 'e'){
                        return intval($intBuffer);
                    }elseif(ctype_digit($input[$i])){
                        $intBuffer .= $input[$i];
                    }else{
                        echo 'int didn\'t end?';
                    }
                }
            case ctype_digit($input[$i]):
                $length = $input[$i];
                while (isset($input[++$i])){
                    if($input[$i] == ':'){
                        break;
                    }elseif(ctype_digit($input[$i])){
                        $length .= $input[$i];
                    }
                }
                $stop = $i + intval($length);
                $string = "";
                while (isset($input[++$i])){
                    if($i <= $stop){
                        $string .= $input[$i];
                        if ($i == $stop){
                            return $string;
                        }
                    }
                }
        }
    }

    private static function makeString($string)
    {
        return strlen($string) . ':' . $string;
    }

    private static function makeInt($int)
    {
        if (is_int($int)) {
            return 'i' . $int . 'e';
        }
    }

    private static function makeList($list)
    {
        $retString = 'l';
        foreach ($list as $item) {
            if (is_array($item)) {
                if (self::isDictionary($item)) {
                    $retString .= self::makeDictionary($item);
                } else {
                    $retString .= self::makeList($item);
                }
                continue;
            }
            if (is_int($item)) {
                $retString .= self::makeInt($item);
                continue;
            }
            if (is_string($item)) {
                $retString .= self::makeString($item);
                continue;
            }
        }
        return $retString .= 'e';
    }

    private static function makeDictionary($dictionary)
    {
        $retString = 'd';
        foreach ($dictionary as $key => $item) {
            $retString .= self::makeString($key);
            if (is_array($item)) {
                if (self::isDictionary($item)) {
                    $retString .= self::makeDictionary($item);
                } else {
                    $retString .= self::makeList($item);
                }
                continue;
            }
            if (is_int($item)) {
                $retString .= self::makeInt($item);
                continue;
            }
            if (is_string($item)) {
                $retString .= self::makeString($item);
                continue;
            }
        }
        return $retString .= 'e';
    }

    private static function isDictionary($dictionary)
    {
        $i = 0;
        foreach ($dictionary as $key => $item) {
            if ($key !== $i++) {
                return true;
            }
        }
        return false;
    }
}
