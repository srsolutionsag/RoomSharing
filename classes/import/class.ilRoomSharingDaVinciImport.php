<?php

require_once 'Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/rooms/detail/class.ilRoomSharingRoom.php';
require_once("Customizing/global/plugins/Services/Repository/RepositoryObject/RoomSharing/classes/booking/class.ilRoomSharingBook.php");

/**
 * Description of class
 *
 * @author MartinDoser
 */
class ilRoomSharingDaVinciImport {

        private $parent_obj;
        private $lng;
        private $pool_id;
        private $ilRoomSharingDatabase;
        
        private $startingDate;
        private $blocks;
        private $units;
        private $mins;
        private $startingTimes;
        private $appointments_info;
        private $appointments;
        private $rooms;
        private $bookings;
        private $currentCourse;
        private $activeWeeks;
        private $current_weekly_rotation;
        private $current_classes;
        
        private $count_Bookings_without_Room;
        private $count_Rooms_created;
        private $count_Bookings_created;
        
        /**
         * Constructor
         * 
         * @param type $a_parent_obj
         * @param type $lng
         * @param type $a_pool_id
         * @param type $a_ilRoomsharingDatabase
         */
	public function __construct($a_parent_obj, $lng, $a_pool_id, $a_ilRoomsharingDatabase)
	{
                $this->parent_obj = $a_parent_obj;
                $this->lng = $lng;
		$this->pool_id = $a_pool_id;
		$this->ilRoomSharingDatabase = $a_ilRoomsharingDatabase;
                
                $this->appointments_info = array();
                $this->appointments = array();
                $this->rooms = array();
                $this->bookings = array();
	}
        
        /**
         * Imports rooms and bookings from a given daVinci file
         * 
         * @param type $file    daVinci text file from file upload form
         * @param boolean $import_rooms    true to import rooms
         * @param boolean $import_bookings true to import bookings
         * @param int $default_cap sets the default room capacity
         */
        public function importBookingsFromDaVinciFile($file, $import_rooms, $import_bookings, $default_cap)
        {
                $this->count_Bookings_without_Room = 0;
                $this->count_Rooms_created = 0;
                $this->count_Bookings_created = 0;
            
                $file_name = ilUtil::getASCIIFilename($file["name"]);
		$file_name_mod = str_replace(" ", "_", $file_name);
		$file_path = "templates" . "/" . $file_name_mod; // construct file path
		ilUtil::moveUploadedFile($file["tmp_name"], $file_name_mod, $file_path);
                
                $fileAsString = file_get_contents($file_path);

                ilUtil::sendInfo($this->lng->txt("rep_robj_xrs_daVinci_import_message_start"),true);
                
                foreach(preg_split("/((\r?\n)|(\r\n?))/", $fileAsString)as$line)
                {
                    $this->checkForKey($line);
                }
                
                ilUtil::sendInfo($this->createInfoMessage($import_rooms, $import_bookings),true);
                
                if($import_rooms === "1")
                {
                    foreach ($this->rooms as $room) {
                        if(!($this->ilRoomSharingDatabase->getRoomWithName($room['name']) !== array()))
                        {
                            if($room['cap'] == 0)
                            {
                                $room['cap'] = (int)$default_cap;
                            }
                            //$a_name, $a_type, $a_min_alloc, $a_max_alloc, $a_file_id, $a_building_id
                            $this->ilRoomSharingDatabase->insertRoom($room['name'],$room['type'],1,$room['cap'],array(),array());
                            $this->count_Rooms_created++;
                        }
                    }
                }
                
                if($import_bookings === "1")
                {
                    foreach($this->appointments as $booking)
                    {  
                        if($booking['day'] != 0)
                        {
                            $usedWeek = clone($this->startingDate);
                            for($i = 0; $i < strlen($this->activeWeeks); $i++)
                            {
                                if($booking['week'] != null)
                                {
                                    if($booking['week'][$i] === 'X')                            
                                    {
                                       $this->addDaVinciBooking($booking['day'], $booking['start'], $booking['end'], $booking['room'], $booking['prof'], $booking['subject'], $booking['classes'], $usedWeek);
                                    }
                                }
                                else
                                {
                                    if($this->activeWeeks[$i] === 'X')                            
                                    {
                                        $this->addDaVinciBooking($booking['day'], $booking['start'], $booking['end'], $booking['room'], $booking['prof'], $booking['subject'], $booking['classes'], $usedWeek);
                                    }
                                }


                                $usedWeek->add(new DateInterval('P7D'));
                            }
                        }
                    }
                }
                
                
                $this->displayInfo();
                
        }
        
        /**
         * interprets a string
         * 
         * @param string $line  line to be interpreted
         */
        private function checkForKey($line)
        {
            $params = preg_split('/;/', $line);
            
            if(strncmp($params[0], "R1", 2) == 0)
            {
                $this->interpretR1Line($params);
            }
            if(strncmp($params[0], "U0", 2) == 0)
            {
                $this->interpretU0Line($params);
            }
            if(strncmp($params[0], "U1", 2) == 0)
            {
                $this->interpretU1Line($params);
            }
            if(strncmp($params[0], "U5", 2) == 0)
            {
                $this->interpretU5Line($params);
            }
            if(strncmp($params[0], "U6", 2) == 0)
            {
                $this->interpretU6Line($params);
            }
            if(strncmp($params[0], "U2", 2) == 0)
            {
                $this->interpretU2Line($params);
            }
            if(strncmp($params[0], "U8", 2) == 0)
            {
                $this->interpretU8Line($params);
            }
        }
        
        /**
         * interprets the a line starting with R1 and adds the room to the array
         * 
         * @param array $params array containing information about the room as strings
         */
        private function interpretR1Line($params)
        {
            array_push($this->rooms, array('name'=>$this->alterString($params[2]),'full_name'=>$params[3],'cap'=>$params[5],'type'=>$params[8]));
        }
        
        /**
         * interprets a line starting with U0 and sets the infomation
         * 
         * @param array $params array containing information about the courses and bookings in daVinci
         */
        private function interpretU0Line($params)
        {
            $dateStr = substr($params[1], 0, 4) . '-' . substr($params[1], 4, 2) . '-' . substr($params[1], 6,2);
            $this->startingDate = new DateTime($dateStr);
            $day_off_set = $this->startingDate->format('N');
            $this->startingDate->sub(new DateInterval('P'. $day_off_set . 'D'));
            $this->blocks = $params[4];
            $this->units = $params[5];
            $this->mins = $params[8];

            $tmpArray = array();
            for($i = 9; $i < (count($params))-1;$i++)
            {
                array_push($tmpArray, date_create($params[$i]));
            }
            $this->startingTimes = $tmpArray;
            
            $this->activeWeeks = $params[3];
        }
    
        
        /**
         * interprets a line starting with U1 and sets the information
         * 
         * @param array $params array containing the information about a course in daVinci
         */
        private function interpretU1Line($params)
        {
            $this->current_weekly_rotation = array();
            $this->current_classes = array();
            $this->currentCourse = $this->alterString($params[6]);
            array_push($this->appointments_info, array('id'=>$params[1],'course'=>$params[2],'prof'=>$params[3],'identifier'=>$params[6]));
        }
        
        /**
         * interprets a line starting with U5 and sets the information
         * 
         * @param array $params array containing the information about the weekly rotation of a course
         */
        private function interpretU5Line($params)
        {
            $this->current_weekly_rotation = $params[2];
        }
        
        /**
         * interprets a line starting with U6 and sets the information
         * 
         * @param array $params array containing the course name of the booking
         */
        private function interpretU6Line($params)
        {
            for($i = 3; $i < ($params[2]+2); $i++)
            {
                if($this->current_classes == null)
                {
                    $this->current_classes = $params[$i];
                }
                else{
                    $this->current_classes = $this->current_classes . ' ' . $params[$i];
                }
            }
        }
        
        /**
         * interprets a line starting with U2 and sets the information
         * 
         * @param array $params array containing the time, room and prof. of the booking
         */
        private function interpretU2Line($params)
        {
         
            for($i = 0;$i < ($params[2]);$i++)
            {
                $n=($i*6+3);
                $day = $params[$n];
                if(($params[$n+1])!= null)
                {
                    $startTime = clone($this->startingTimes[($params[$n+1])-1]);
                    $endTime = clone($this->startingTimes[($params[$n+1])-1]);
                    $endTime->add(new DateInterval('PT'.$this->mins.'M'));
                }
                $roomShrt = $params[$n+3];
                $profShrt = $params[$n+4];
                
                array_push($this->appointments, array('id'=>$params[1],'day'=>$day,'start'=>$startTime,'end'=>$endTime,'room'=>$this->alterString($roomShrt),
                    'prof'=>$this->alterString($profShrt),'subject'=>$this->currentCourse,'classes'=>$this->current_classes,'week'=>  $this->current_weekly_rotation));
            }
        }
        
        /**
         * interprets a line starting with U8 and sets the information
         * used for daVinci 6
         * ----------untested----------
         * 
         * @param array $params array containing the time, room and prof. of the booking
         */
        private function interpretU8Line($params)
        {
            //used for daVinci6
            for($i = 0;$i < ($params[2]);$i++)
            {
                $n=($i*10+3);
                $day = $params[$n+2];
                if($params[$n] != null && $params[$n+1] != null)
                {
                    $startTime = clone($this->startingTimes[($params[$n+5])-1]);
                    $endTime = clone($this->startingTimes[($params[$n+1])-1]);
                    $endTime->add(new DateInterval('PT'.$params[$n+4].'M'));
                }
                $roomShrt = $params[$n+7];
                $profShrt = $params[$n+6];
                
                array_push($this->appointments, array('id'=>$params[1],'day'=>$day,'start'=>$startTime,'end'=>$endTime,'room'=>$this->alterString($roomShrt),
                    'prof'=>$this->alterString($profShrt),'subject'=>$this->currentCourse,'classes'=>$this->current_classes,'week'=>  $this->current_weekly_rotation));
            }
        }

        
        /**
         * Removes the quotation ("") of the beginning and end of a string
         * 
         * @param string $aString
         * @return string
         */
        private function alterString($aString)
        {
            if($aString[0] == '"' && $aString[strlen($aString)-1] == '"' )
            {
                $aString = substr($aString, 1, strlen($aString)-2);
            }
            
            return $aString;
        }
        
        /**
         * adds the bookings with the given information to the roomsharing system
         * 
         * @param int $day
         * @param dateTime $start
         * @param dateTime $end
         * @param string $room
         * @param string $prof
         * @param string $subject
         * @param string $classes
         * @param dateTime $usedWeek
         */  
        private function addDaVinciBooking($day, $start, $end, $room, $prof, $subject, $classes, $usedWeek)
        {
            $date_diff = clone($usedWeek);
            $interval = $date_diff->diff(new DateTime(date('Y-m-d')));

            if(($interval->format('%R'))=== '-')
            {
                $entry = array();

                if($classes == null)
                {
                    $entry['subject'] = ($subject . " " . $prof);
                }
                else
                {
                    $entry['subject'] = ($classes . " " . $subject . " " . $prof);
                }

                $tmpDate = clone($usedWeek);
                $tmpDate->add(new DateInterval('P'. (string)$day . 'D'));
                $entry['from']['date']=  date_format($tmpDate,'Y-m-d');
                $entry['from']['time']= date_format($start, 'H:i:s');
                $entry['to']['date']= date_format($tmpDate,'Y-m-d');
                $entry['to']['time']=  date_format($end, 'H:i:s');
                $entry['book_public'] = '0';
                $entry['accept_room_rules'] = '1';
                
                $entry['room'] = $this->ilRoomSharingDatabase->getRoomWithName($room)[0]['id'];
                $entry['comment'] = $this->lng->txt("rep_robj_xrs_daVinci_import_tag");
                $entry['cal_id'] = $this->parent_obj->getCalendarId();


                
                $this->book = new ilRoomSharingBook($this->pool_id);
                
                if($this->ilRoomSharingDatabase->getRoomWithName($room) !== array())
                {
                    $aBooking = $this->ilRoomSharingDatabase->getBookingIdForRoomInDateTimeRange($entry['from']['date'] . " "  . $entry['from']['time'], $entry['to']['date'] . " "  . $entry['to']['time'], $entry['room'],
                            0);
                   
                    if ($aBooking === array() || $this->ilRoomSharingDatabase->getBooking($aBooking[0])['bookingcomment'] !== $this->lng->txt("rep_robj_xrs_daVinci_import_tag"))
                    {
                        try {
                            $this->book->addBooking($entry,array(),array(),array(),false);
                            $this->count_Bookings_created++;
                        } catch (Exception $ex) {
                            
                        }
                    }
                    elseif ($this->ilRoomSharingDatabase->getBooking($aBooking[0])['bookingcomment'] === $this->lng->txt("rep_robj_xrs_daVinci_import_tag")) {
                            $newBookingValues = $this->ilRoomSharingDatabase->getBooking($aBooking[0]);
                            $newBookingValues['subject'] = $newBookingValues['subject'] . ' & ' . $subject . ' ' . $prof;
                            $newBookingValues['from'] = $entry['from'];
                            $newBookingValues['to'] = $entry['to'];
                            $newBookingValues['room'] = $entry['room'];
                            $newBookingValues['cal_id'] = $entry['cal_id'];
                            $newBookingValues['comment'] = $this->lng->txt("rep_robj_xrs_daVinci_import_tag");

                        try
                        {
                            $this->book->updateEditBooking(
                                $aBooking[0], 
                                $this->ilRoomSharingDatabase->getBooking($aBooking[0]),
                                $this->ilRoomSharingDatabase->getAttributesForBooking($aBooking[0]),
                                $this->ilRoomSharingDatabase->getParticipantsForBooking($aBooking[0]), 
                                $newBookingValues,
                                $this->ilRoomSharingDatabase->getAttributesForBooking($aBooking[0]),
                                $this->ilRoomSharingDatabase->getParticipantsForBooking($aBooking[0]),
                                false);
                        } catch (Exception $ex) {
                                
                        }
                    }
                }
                else
                {
                    $this->count_Bookings_without_Room++;
                }
            }
        }
        
        private function displayInfo()
        {
            if($this->count_Bookings_without_Room == 1)
            {
                ilUtil::sendFailure($this->count_Bookings_without_Room . ' ' . $this->lng->txt("rep_robj_xrs_daVinci_import_error_no_room_sing") ,true);
            }
            if($this->count_Bookings_without_Room > 1)
            {
                ilUtil::sendFailure($this->count_Bookings_without_Room . ' ' . $this->lng->txt("rep_robj_xrs_daVinci_import_error_no_room_plu"),true);
            }
            
            ilUtil::sendSuccess($this->createSuccessMessage(),true);
            ilUtil::sendInfo($this->lng->txt("rep_robj_xrs_daVinci_import_message_end"),true);
        }
        
        private function createSuccessMessage()
        {
            $successText = "";
            
            if($this->count_Rooms_created == 1){
                $successText = $successText . ' ' . $this->count_Rooms_created . ' ' . $this->lng->txt("rep_robj_xrs_daVinci_import_succ_room_created");
                $successText = $successText . '<br>';
            } elseif ($this->count_Rooms_created > 1) {
                $successText = $successText . ' ' . $this->count_Rooms_created . ' ' . $this->lng->txt("rep_robj_xrs_daVinci_import_succ_rooms_created");
                $successText = $successText . '<br>';
            }

            if($this->count_Bookings_created == 1){
                $successText = $successText . ' ' . $this->count_Bookings_created . ' ' . $this->lng->txt("rep_robj_xrs_daVinci_import_succ_booking_created");
            }elseif ($this->count_Bookings_created > 1) {
                $successText = $successText . ' ' . $this->count_Bookings_created . ' ' . $this->lng->txt("rep_robj_xrs_daVinci_import_succ_bookings_created");
            }
            
            return $successText;
        }
        
        private function createInfoMessage($import_rooms, $import_bookings)
        {
            $infoText = $this->lng->txt("rep_robj_xrs_daVinci_import_message_start") . '<br>';
            
            if($import_bookings === "1")
            {
                if(count($this->appointments) == 1){
                    $infoText = $infoText . ' ' . count($this->appointments) . ' ' . $this->lng->txt("rep_robj_xrs_daVinci_import_message_appointment") ;
                    $infoText = $infoText . '<br>';
                } elseif (count($this->appointments) > 1 || count($this->appointments) == 0) {
                    $infoText = $infoText . ' ' . count($this->appointments) . ' ' . $this->lng->txt("rep_robj_xrs_daVinci_import_message_appointments");
                    $infoText = $infoText . '<br>';
                }
            }

            if($import_rooms === "1")
            {
                if(count($this->rooms) == 1){
                    $infoText = $infoText . ' ' . count($this->rooms) . ' ' . $this->lng->txt("rep_robj_xrs_daVinci_import_message_room");
                } elseif (count($this->rooms)> 1 || count($this->rooms) == 0) {
                    $infoText = $infoText . ' ' . count($this->rooms) . ' ' . $this->lng->txt("rep_robj_xrs_daVinci_import_message_rooms");;
                }
            }
            
            return $infoText;
        }
}
