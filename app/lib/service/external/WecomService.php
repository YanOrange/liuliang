<?php

namespace app\lib\service\external;

use app\lib\service\common\CurlService;
use app\model\external\WeComCustomer;

class WecomService {

    const URL_TOKEN = "https://qyapi.weixin.qq.com/cgi-bin/gettoken";
    private $token;

    public function __construct($configAttr = [])
    {
        if ($configAttr) {
            $this->token = $this->getApiToken($configAttr['corp_id'], $configAttr['corp_secret']);
        }
    }

    public function test($params)
    {
//        $redis = get_redis();
//        $redis->select(1);
//        $redisKey = 'test:wecom:callback';
//        $redis->hSet($redisKey, date('md H:i:s.' . rand(10, 99)), json_encode($params));

        $token = 'gjhE5Y';
        $aesKey = '3LQoGq7xOGDjCOEn0LnczFP4lzWNQ4lipF3xtVgY9VL';
//        "echostr": "kLwJuhT02hYhFfEUmQKp\/5rCsoKvo4uoKHk1mxK3D8VyANej6JhTpNXaMRvhRdylmssb\/AAZe9XfN0lPnEVl8Q=="
//        $params = '{
//  "msg_signature": "1cd2fd493353c304e00ec27a81a052c1f8a15543",
//  "timestamp": "1723527225",
//  "nonce": "1723009662",
//
//  "echostr": "ZwWUKnlo7RXCLdA72aDbvoVxZtCDKpEcqXpb9q8\/AbJkclW6sFzipNlcOxBZDJc+alRVQNw6YfACiUsAwlabHSxoEzIobf67ogBLCvV6AYnKU0m5S2J3JydCsqDc4y\/yEZKXoMjTZwrcRK79bMxeKrdSe98hRCPLiumFNmCbovBC4JCAoJGBaGUtEBLzALqveurd2ttHsHVDXLIo6\/EHA9Kvldi64qUVzRFtXZyoE2lfAoWqaDCysetxqnotSyJWytpL7SRhmPU6SqGD24GNTBwatE+4Pe6iCkZg5tAVJRFStJGN6Bf69UGr\/gwXrSNshMcZxdBGVPrQWOwBAg0jP2h\/JDTivoH9BgId\/KtTyQfOa6p\/Y+YsCXRMF3zn6HMOPgWs3oipMsrLIJYQx8q5MZR85b8zG92MuLX\/o2CEuqFPKu42LDZjIOMcWl5PlY\/0A9NyiHMbIj6v6Mrd+ydyTkUE2GpegWViX3osbf0rUy8V9fTffrDB7IqB8013kE9Rm008Y89hphSnILsCIOPxX0o+yB4on8W5jMznq6Bp5YcbbMOxBJJJ0jxN89zcsBl+8JC+tWvKwgaa8uwyLcl+tg=="
//}';
//        $params = json_decode($params, true);

        $echostr = $params['echostr'];

        $key = base64_decode($aesKey . '=');
        $iv  = substr($key, 0, 16);
        try {
            //解密
            $decrypted = openssl_decrypt($echostr, 'AES-256-CBC', $key, OPENSSL_ZERO_PADDING, $iv);
        } catch (\Exception $e) {
            return false;
        }
        try {
            //删除PKCS#7填充
            $result      = $this->pkcs7_decode($decrypted);
            if (strlen($result) < 16) {
                return false;
            }
            //拆分
            $content     = substr($result, 16, strlen($result));
            $len_list    = unpack('N', substr($content, 0, 4));
            $json_len     = $len_list[1];
            $json_content = substr($content, 4, $json_len);
            $from_receiveId = substr($content, $json_len + 4);
        } catch (\Exception $e) {
            return false;
        }
        $jsonxml = json_encode(simplexml_load_string($json_content, 'SimpleXMLElement', LIBXML_NOCDATA));
        $xmlData = json_decode($jsonxml, true);

        print_r($xmlData);exit;
        return $json_content;
    }

    /**
     * 对解密后的明文进行补位删除
     * @param string $text 解密后的明文
     * @return string 删除填充补位后的明文
     */
    protected function pkcs7_decode(string $text): string
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

    public function syncCustomer($configAttr)
    {
        $this->token = $this->getApiToken($configAttr['corp_id'], $configAttr['corp_secret']);

        $redis = get_redis();
        $redis->select(5);
        $key = 'external:wecom:syncWeComCustomer:unique_limit_hash_key';

        # 获取企业用户ids
        $userIds = $this->getExternalContactFollowUserList();

        # 获取企业微信标签
        $tagsList = $this->getTagList();
        $tagsList = $tagsList ? array_column($tagsList, 'name', 'id') : [];

        $result = ['success' => [], 'error' => []];
        foreach ($userIds as $userId) {

            # 获取用户下客户信息
            $customerData = $this->getBatchExternalContact([$userId]);
            if (! $customerData) continue;

            foreach ($customerData as $row) {

                $externalUserId = $row['external_contact']['external_userid'] ?? '';

                $externalUserName = $row['external_contact']['name'] ?? '';
                $externalUserName = preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', $externalUserName);

                $remark = $row['follow_info']['remark'] ?? '';
                $remark = preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', $remark);

                if ($redis->hGet($key, $externalUserId))    continue;

                $tagArr = $row['follow_info']['tag_id'] ?? [];
                $tagId = $tagArr[0] ?? '';
                $channel = $tagId ? ($tagsList[$tagId] ?? '') : '';

                $data = [
                    'merchant_id'           => $configAttr['merchant_id'],
                    'external_userid'       => $externalUserId,                                     # 外部联系人的userid
                    'external_user_name'    => $externalUserName,              # 外部联系人的name
                    'external_user_type'    => $row['external_contact']['type'] ?? 0,               # 外部联系人的类型（1：微信用户，2：企微用户）
                    'external_user_gender'  => $row['external_contact']['gender'] ?? 0,             # 外部联系人的性别（0-未知 1-男性 2-女性）
                    'external_user_corp_name'   => $row['external_contact']['corp_name'] ?? 0,      # 外部联系人所在企业的简称，仅当联系人类型是企业微信用户时有此字段
                    'userid'                    => $row['follow_info']['userid'] ?? '',             # 添加了此外部联系人的企业成员userid
                    'remark'                    => $remark,             # 该成员对此外部联系人的备注
                    'add_way'                   => $row['follow_info']['add_way'] ?? 0,             # 该成员添加此客户的来源
                    'oper_userid'               => $row['follow_info']['oper_userid'] ?? '',        # 发起添加的userid(如果成员主动添加，为成员的userid；如果是客户主动添加，则为客户的外部联系人userid；如果是内部成员共享/管理员分配，则为对应的成员/管理员userid)
                    'state'                     => $row['follow_info']['state'] ?? '',              # 企业自定义的state参数
                    'add_time'                  => $row['follow_info']['createtime'] ?? 0,          # 该成员添加此外部联系人的时间
                    'response'                  => json_encode($row),
                    'channel'                   => $channel,
                ];

                $log = WeComCustomer::create($data);

                $redis->hSet($key, $externalUserId, $log ? $log->id : time());

                $result['success'][] = $externalUserId;
            }
        }
        return $result;
    }

    /**
     * 获取接口token
     * @param string $corpid
     * @param string $corpsecret
     * @return string
     * @throws \RedisException
     */
    protected function getApiToken(string $corpid, string $corpsecret): string
    {
        $key = "external:wecom:sync:token_{$corpid}_{$corpsecret}";
        if ( $token = get_redis()->get($key) ) {
            return $token;
        }

        $data = CurlService::get(self::URL_TOKEN, ['corpid' => $corpid, 'corpsecret' => $corpsecret]);
        $data = $data ? json_decode($data, true) : [];

        if (! isset($data['errcode']) || $data['errcode'] !== 0) {
            return $token;
        }

        $token = $data['access_token'] ?? '';
        get_redis()->setex($key, $data['expires_in'], $token);

        return $token;
    }

    /**
     * 获取配置了客户联系功能的成员列表
     */
    public function getExternalContactFollowUserList(): array
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get_follow_user_list?access_token=" . $this->token;

        # 请求企微接口
        return $this->requestApi($url, [], 'follow_user', [], 0, 'GET');
    }

    /**
     * 批量获取客户详情
     */
    public function getBatchExternalContact($userIds): array
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/batch/get_by_user?access_token={$this->token}";

        $requestData = [
            'userid_list'   => $userIds,
            'limit'         => 100,
        ];

        # 请求企微接口
        return $this->requestApi($url, $requestData, 'external_contact_list');
    }

    /**
     * 获取标签
     */
    public function getTagList()
    {
        $key = "external:wecom:tags_list:$this->token";
        $result = get_redis()->get($key);
        if ($result)    return json_decode($result, true);

        $url = "https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get_corp_tag_list?access_token={$this->token}";

        # 请求企微接口
        $data = $this->requestApi($url, [], 'tag_group');

        $result = [];
        foreach ($data as $row) {

            foreach ($row['tag'] as $item) {
                $result[] = ['id' => $item['id'], 'name' => $item['name'], 'group_id' => $row['group_id'], 'group_name' => $row['group_name']];
            }
        }
        get_redis()->setex($key, 60, json_encode($result));
        return $result;
    }

    /**
     * 请求企微接口
     * @param string $url 请求地址
     * @param array $requestData 请求数据
     * @param string $apiNeedField 接口返回结果需要的数据字段名
     * @param array $data 结果载体（用于递归处理）
     * @param int|string $cursor 分页查询的游标（默认每次请求返回1000条，通过该字段判断是否还有数据）
     * @return array
     */
    protected function requestApi(string $url, array $requestData, string $apiNeedField, array $data = [], $cursor = 0, string $method = "POST"): array
    {
        # 递归终止条件
        if ($cursor == -1)  return $data;

        # 请求数据更新分页游标
        if ($cursor)    $requestData['cursor'] = $cursor;

        # 请求企微接口
        if (strtoupper($method) == "POST") {
            $apiData = CurlService::postRequest($url, json_encode($requestData), ['content-type: application/json; charset=UTF-8']);
        } else {
            $apiData = CurlService::getRequest($url, $requestData);
        }
        $apiData = $apiData ? json_decode($apiData, true) : [];

        # 处理接口结果，合并所需的数据
        $dataList   = [];
        $cursor     = -1;
        if (isset($apiData['errcode']) && $apiData['errcode'] === 0) {
            $dataList   = $apiNeedField ? $apiData[$apiNeedField] : $apiData;
            $cursor     = isset($apiData['next_cursor']) && $apiData['next_cursor'] ? $apiData['next_cursor'] : -1;
        }
        $data = $dataList ? array_merge($data, $dataList) : $data;

        # 开始递归
        return $this->requestApi($url, $requestData, $apiNeedField, $data, $cursor);
    }
}