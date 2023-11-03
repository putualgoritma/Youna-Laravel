<?php

namespace App\Classes;

use App\CustomerApi;
use App\LogNotif;
use OneSignal;

class NotifClass
{
    public function __construct()
    {
    }

    public function notifSend($member_id, $memo)
    {
        //push notif to agent
        $user_os = CustomerApi::find($member_id);
        $id_onesignal = $user_os->id_onesignal;
        $register = date("Y-m-d");
        //store to logs_notif
        $data = ['register' => $register, 'customers_id' => $member_id, 'memo' => $memo];
        $logs = LogNotif::create($data);
        //push notif
        if (!empty($id_onesignal)) {
            OneSignal::sendNotificationToUser(
                $memo,
                $id_onesignal,
                $url = null,
                $data = null,
                $buttons = null,
                $schedule = null
            );}
    }
}
