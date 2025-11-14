<?php

namespace WorkWeixin\callback;
include_once "WXBizMsgCrypt.php";

/**
 * Notes:
 * Data:2023-11-02
 * @Author:TalkTrue 705219520@qq.com
 * @return
 */
class WorkWeixinCallback
{

	public $WXBizMsgCrypt;
	function __construct($token,$encodingAesKey,$receiveId)
	{
		$this->WXBizMsgCrypt = (new \WXBizMsgCrypt($token, $encodingAesKey, $receiveId));
	}
}