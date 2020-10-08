<?php
    namespace whatwhat\file;

    class Directory{
        static public function scandir($dir, $filter = null, $create = false){
            if($create) self::isdir($dir);
            elseif(!is_dir($dir)) return false;
            $no = array('.', '..', '.DS_STORE', 'Thumbs.db');
            $temp = array_diff(scandir($dir), $no);
            if(!is_null($filter)){
                foreach($temp as $key => &$file){
                    if(strpos($file, $filter) === false) unset($temp[$key]);
                }
            }
            return $temp;
        }
        
        static public function isdir($dir){
            if(!is_dir($dir) && !mkdir($dir)) throw new \Exception("Can't find or create ".$dir." directory.");
        }

        static public function mkpath($path){
            if(is_string($path)){
                $arraypath = explode('/', $path);
            }elseif(is_array($path)){
                $arraypath = $path;
            }else{
                paramcheck($path, array('string', 'array'));
            }

            $currentPath = '';
            foreach($arraypath as $p){
                $currentPath .= $p.'/';
                self::isdir($currentPath);
            }
        }

        static public function getLatestSaveDir($savesDirectory, $dbname){
            $saves = Directory::scandir($savesDirectory, $dbname);
            if($saves !== false){
                $mostRecent['year'] =  0;
                $mostRecent['month'] = 0;
                $mostRecent['day'] = 0;
                $mostRecent['hour'] = 0;
                $mostRecent['minute'] = 0;
                $mostRecent['second'] = 0;
                $lendb = strlen($dbname);
                foreach($saves as $save){
                    $date = substr($save, $lendb, strlen($save) - ($lendb));
                    $data = explode('_', $date);
                    $greatDate = $data[0];
                    $littleDate = $data[1];
                    $data1 = explode('-', $greatDate);
                    $data2 = explode('-', $littleDate);
                    $year = $data1[0];
                    $month = $data1[1];
                    $day = $data1[2];
                    $hour = $data2[0];
                    $minute = $data2[1];
                    $second = $data2[2];
                    if($mostRecent['year'] < $year
                        || ($mostRecent['year'] == $year && $mostRecent['month'] < $month)
                        || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] < $day)
                        || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] == $day 
                                && $mostRecent['hour'] < $hour)
                        || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] == $day 
                                && $mostRecent['hour'] == $hour && $mostRecent['minute'] < $minute)
                        || ($mostRecent['year'] == $year && $mostRecent['month'] == $month && $mostRecent['day'] == $day 
                                && $mostRecent['hour'] == $hour && $mostRecent['minute'] == $minute && $mostRecent['second'] < $second)){
                        $mostRecent['year'] = $year;
                        $mostRecent['month'] = $month;
                        $mostRecent['day'] = $day;
                        $mostRecent['hour'] = $hour;
                        $mostRecent['minute'] = $minute;
                        $mostRecent['second'] = $second;
                    }
                }
                if($mostRecent['year'] == 0) die('No saves found in '.$savesDirectory);
                return $savesDirectory.$dbname
                            .$mostRecent['year']."-".$mostRecent['month']."-".$mostRecent['day']
                            ."_".$mostRecent['hour']."-".$mostRecent['minute']."-".$mostRecent['second'];
            }else{
                die('No saves directory found here '.$savesDirectory);
            }
        }
    }