<?php

namespace Sovit\TikTok;

if (!\class_exists('\Sovit\TikTok\Api')) {
    class Api
    {
        const API_BASE = "https://www.tiktok.com/node/";

        private $_config = [];

        private $cache = false;

        private $cacheEnabled = false;

        private $defaults = [
            "user-agent"     => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.75 Safari/537.36',
            "proxy-host"     => false,
            "proxy-port"     => false,
            "proxy-username" => false,
            "proxy-password" => false,
            "cache-timeout"  => 3600, // in seconds
        ];
        public function __construct($config = array(), $cacheEngine = false)
        {
            $this->_config = array_merge(['cookie_file' => sys_get_temp_dir() . 'tiktok.txt'], $this->defaults, $config);
            if ($cacheEngine) {
                $this->cacheEnabled = true;
                $this->cache        = $cacheEngine;
            }
            // if (!file_exists($this->_config['cookie_file'])) {
            //     $this->remote_call("https://www.tiktok.com/foryou?lang=en", 'tiktok-init');
            // }
            // $browser = \explode("/", $this->_config['user-agent'], 2);
            // $this->default_params = array(
            //     'aid' => 1988,
            //     'app_name' => 'tiktok_web',
            //     'device_platform' => 'web',
            //     'referer' => 'https://www.tiktok.com/',
            //     'user_agent' => $this->_config['user-agent'],
            //     'cookie_enabled' => true,
            //     'screen_width' => 1366,
            //     'screen_height' => 768,
            //     'browser_language' => 'en-US',
            //     'browser_platform' => 'Win32',
            //     'browser_name' => $browser[0],
            //     'browser_version' => $browser[1],
            //     'browser_online' => true,
            //     'ac' => '4g',
            //     'timezone_name' => 'EST',
            //     'appId' => 1233,
            //     'appType' => 'm',
            //     'isAndroid' => false,
            //     'isMobile' => false,
            //     'isIOS' => false,
            //     'OS' => 'windows',
            //     'did' => random(100,999999999),

            // );
        }

        public function getChallenge($challenge = "")
        {
            if (empty($challenge)) {
                throw new \Exception("Invalid Challenge");
            }
            $result = $this->remote_call(self::API_BASE . "share/tag/{$challenge}", 'challenge-' . $challenge);
            if (isset($result->challengeInfo)) {
                return (object) [
                    'id'            => @$result->challengeInfo->challenge->id,
                    'title'         => @$result->challengeInfo->challenge->title,
                    'desc'          => @$result->challengeInfo->challenge->desc,
                    'coverThumb'    => @$result->challengeInfo->challenge->coverThumb,
                    'coverMedium'   => @$result->challengeInfo->challenge->coverMedium,
                    'coverLarger'   => @$result->challengeInfo->challenge->coverLarger,
                    'profileThumb'  => @$result->challengeInfo->challenge->profileThumb,
                    'profileMedium' => @$result->challengeInfo->challenge->profileMedium,
                    'profileLarger' => @$result->challengeInfo->challenge->profileLarger,
                    'isCommerce'    => @$result->challengeInfo->challenge->isCommerce,
                    "stats"         => (object) [
                        'videoCount' => @$result->challengeInfo->stats->videoCount,
                        'viewCount'  => @$result->challengeInfo->stats->viewCount
                    ],

                ];
            }
            return false;
        }

        public function getChallengeFeed($challenge_name = "", $maxCursor = 0)
        {
            if (empty($challenge_name)) {
                throw new \Exception("Invalid Challenge");
            }
            $challenge = $this->getChallenge($challenge_name);
            if ($challenge) {
                $param = [
                    "type"      => 3,
                    "secUid"    => "",
                    "id"        => $challenge->id,
                    "count"     => 30,
                    "minCursor" => 0,
                    "maxCursor" => $maxCursor,
                    "shareUid"  => "",
                    "lang"      => "",
                    "verifyFp"  => "",
                ];
                $result = $this->remote_call(self::API_BASE . "video/feed?" . http_build_query($param), 'challenge-' . $challenge_name . '-' . $maxCursor);
                if (isset($result->body->itemListData)) {
                    return (object) [
                        "statusCode" => 0,
                        "info"       => (object) [
                            'type'   => 'challenge',
                            'detail' => $challenge,
                        ],
                        "items"      => Helper::parseData($result->body->itemListData),
                        "hasMore"    => @$result->body->hasMore,
                        "minCursor"  => $maxCursor,
                        "maxCursor"  => $maxCursor + 30,
                    ];
                }
            }
            return false;
        }

        public function getMusic($music_id = "")
        {
            if (empty($music_id)) {
                throw new \Exception("Invalid Music ID");
            }
            $result = $this->remote_call(self::API_BASE . "share/music/original-sound-{$music_id}", 'music-' . $music_id);
            if (isset($result->musicInfo)) {
                return (object) [
                    'id'          => @$result->musicInfo->music->id,
                    'title'       => @$result->musicInfo->music->title,
                    'playUrl'     => @$result->musicInfo->music->playUrl,
                    'coverThumb'  => @$result->musicInfo->music->coverThumb,
                    'coverMedium' => @$result->musicInfo->music->coverMedium,
                    'coverLarge'  => @$result->musicInfo->music->coverLarge,
                    'authorName'  => @$result->musicInfo->music->authorName,
                    'original'    => @$result->musicInfo->music->original,
                    'private'     => @$result->musicInfo->music->private,
                    'duration'     => @$result->musicInfo->music->duration,
                    'stats'       => (object) [
                        'videoCount' => @$result->musicInfo->stats->videoCount,
                    ],
                ];
            }
            return false;
        }

        public function getMusicFeed($music_id = "", $maxCursor = 0)
        {
            if (empty($music_id)) {
                throw new \Exception("Invalid Music ID");
            }
            $music = $this->getMusic($music_id);
            if ($music) {
                $param = [
                    "type"      => 4,
                    "secUid"    => "",
                    "id"        => $music_id,
                    "count"     => 30,
                    "minCursor" => 0,
                    "maxCursor" => $maxCursor,
                    "shareUid"  => "",
                    "lang"      => "",
                    "verifyFp"  => "",
                ];
                $result = $this->remote_call(self::API_BASE . "video/feed?" . http_build_query($param), 'music-feed-' . $music_id . '-' . $maxCursor);
                if (isset($result->body->itemListData)) {
                    return (object) [
                        "statusCode" => 0,
                        "info"       => (object) [
                            'type'   => 'music',
                            'detail' => $music,
                        ],
                        "items"      => Helper::parseData($result->body->itemListData),
                        "hasMore"    => @$result->body->hasMore,
                        "minCursor"  => @$result->body->minCursor,
                        "maxCursor"  => @$result->body->maxCursor,
                    ];
                }
            }
            return false;
        }

        public function getNoWatermark($url = false)
        {
            if (!preg_match("/https?:\/\/([^\.]+)?\.tiktok\.com/", $url)) {
                throw new \Exception("Invalid VIDEO URL");
            }
            $data = $this->getVideoByUrl($url);
            if ($data) {
                $video = $data->items[0];
                if ($video->createTime < 1595894400) {
                    // only attempt to get video ID before 28th July 2020
                    $ch = curl_init();

                    $options = [
                        CURLOPT_URL            => $video->video->downloadAddr,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HEADER         => false,
                        CURLOPT_HTTPHEADER     => [
                            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                            'Accept-Encoding: gzip, deflate, br',
                            'Accept-Language: en-US,en;q=0.9',
                            'Range: bytes=0-200000',
                        ],
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0',
                        CURLOPT_ENCODING       => "utf-8",
                        CURLOPT_AUTOREFERER    => false,
                        CURLOPT_REFERER        => 'https://www.tiktok.com/',
                        CURLOPT_CONNECTTIMEOUT => 30,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_TIMEOUT        => 30,
                        CURLOPT_MAXREDIRS      => 10,
                    ];
                    curl_setopt_array($ch, $options);
                    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                    }
                    $data     = curl_exec($ch);
                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    $parts = explode("vid:", $data);
                    if (count($parts) > 1) {
                        $video_id = trim(explode("%", $parts[1])[0]);
                        return (object) [
                            "id" => $video_id,
                            "url"                 => Helper::finalUrl("https://api-h2.tiktokv.com/aweme/v1/play/?video_id={$video_id}&vr_type=0&is_play_url=1&source=PackSourceEnum_FEED&media_type=4&ratio=default&improve_bitrate=1"),
                        ];
                    }
                }
            }
            return false;
        }

        public function getTrendingFeed($maxCursor = 0)
        {
            $param = [
                "type"      => 5,
                "secUid"    => "",
                "id"        => 1,
                "count"     => 30,
                "minCursor" => 0,
                "maxCursor" => $maxCursor > 0 ? 1 : 0,
                "shareUid"  => "",
                "lang"      => "en",
                "verifyFp"  => "",
            ];
            $result = $this->remote_call(self::API_BASE . "video/feed?" . http_build_query($param), 'trending-' . $maxCursor);
            if (isset($result->body->itemListData)) {
                return (object) [
                    "statusCode" => 0,
                    "info"       => (object) [
                        'type'   => 'trending',
                        'detail' => false,
                    ],
                    "items"      => Helper::parseData($result->body->itemListData),
                    "hasMore"    => @$result->body->hasMore,
                    "minCursor"  => $maxCursor,
                    "maxCursor"  => ++$maxCursor,
                ];
            }

            return false;
        }

        public function getUser($username = "")
        {
            if (empty($username)) {
                throw new \Exception("Invalid Username");
            }
            $result = $this->remote_call(self::API_BASE . "share/user/@{$username}", 'user-' . $username);
            if (isset($result->userInfo)) {
                return (object) [
                    'avatarLarger' => @$result->userInfo->user->avatarLarger,
                    'avatarMedium' => @$result->userInfo->user->avatarMedium,
                    'avatarThumb'  => @$result->userInfo->user->avatarThumb,
                    'id'           => @$result->userInfo->user->id,
                    'nickname'     => @$result->userInfo->user->nickname,
                    'openFavorite' => @$result->userInfo->user->openFavorite,
                    'relation'     => @$result->userInfo->user->relation,
                    'secUid'       => @$result->userInfo->user->secUid,
                    'secret'       => @$result->userInfo->user->secret,
                    'signature'    => @$result->userInfo->user->signature,
                    'uniqueId'     => @$result->userInfo->user->uniqueId,
                    'verified'     => @$result->userInfo->user->verified,
                    'stats'        => (object) [
                        'diggCount'      => @$result->userInfo->stats->diggCount,
                        'followerCount'  => @$result->userInfo->stats->followerCount,
                        'followingCount' => @$result->userInfo->stats->following,
                        'heart'          => @$result->userInfo->stats->heart,
                        'heartCount'     => @$result->userInfo->stats->heartCount,
                        'videoCount'     => @$result->userInfo->stats->videoCount,
                    ],
                ];
            }
            return false;
        }

        public function getUserFeed($username = "", $maxCursor = 0)
        {
            if (empty($username)) {
                throw new \Exception("Invalid Username");
            }
            $user = $this->getUser($username);
            if ($user) {
                $param = [
                    "type"      => 1,
                    "secUid"    => "",
                    "id"        => $user->id,
                    "count"     => 30,
                    "minCursor" => "0",
                    "maxCursor" => $maxCursor,
                    "shareUid"  => "",
                    "lang"      => "",
                    "verifyFp"  => "",
                ];
                $result = $this->remote_call(self::API_BASE . "video/feed?" . http_build_query($param), 'user-feed-' . $username . '-' . $maxCursor);
                if (isset($result->body->itemListData)) {
                    return (object) [
                        "statusCode" => 0,
                        "info"       => (object) [
                            'type'   => 'user',
                            'detail' => $user,
                        ],
                        "items"      => Helper::parseData($result->body->itemListData),
                        "hasMore"    => @$result->body->hasMore,
                        "minCursor"  => @$result->body->minCursor,
                        "maxCursor"  => @$result->body->maxCursor,
                    ];
                }
            }
            return false;
        }

        public function getVideoByID($video_id = "")
        {
            if (empty($video_id)) {
                throw new \Exception("Invalid VIDEO ID");
            }
            return $this->getVideoByUrl('https://m.tiktok.com/v/' . $video_id . '.html');
        }

        public function getVideoByUrl($url = "")
        {

            if (!preg_match("/https?:\/\/([^\.]+)?\.tiktok\.com/", $url)) {
                throw new \Exception("Invalid VIDEO URL");
            }
            $result      = $this->remote_call($url, Helper::normalize($url), false);
            $result = Helper::string_between($result, '{"props":{"initialProps":{', "</script>");
            if (!empty($result)) {
                $jsonData = json_decode('{"props":{"initialProps":{' . $result);
                if (isset($jsonData->props->pageProps->itemInfo)) {
                    return (object) [
                        'statusCode' => 0,
                        'info'       => (object) [
                            'type'   => 'video',
                            'detail' => $url,
                        ],
                        "items"      => [$jsonData->props->pageProps->itemInfo->itemStruct],
                        "hasMore"    => false,
                        "minCursor"  => '0',
                        "maxCursor"  => ' 0',
                    ];
                }
            }
            return false;
        }

        private function remote_call($url = "", $cacheKey = false, $isJson = true)
        {
            if ($this->cacheEnabled) {
                if ($this->cache->get($cacheKey)) {
                    return $this->cache->get($cacheKey);
                }
            }
            $ch      = curl_init();
            $options = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT      => $this->_config['user-agent'],
                CURLOPT_ENCODING       => "utf-8",
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_HTTPHEADER     => [
                    'Referer: https://www.tiktok.com/foryou?lang=en',
                ],
                CURLOPT_COOKIEJAR      => $this->_config['cookie_file'],
            ];
            if (file_exists($this->_config['cookie_file'])) {
                curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_config['cookie_file']);
            }
            curl_setopt_array($ch, $options);
            if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
            if ($this->_config['proxy-host'] && $this->_config['proxy-port']) {
                curl_setopt($ch, CURLOPT_PROXY, $this->_config['proxy-host'] . ":" . $this->_config['proxy-port']);
                if ($this->_config['proxy-username'] && $this->_config['proxy-password']) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->_config['proxy-username'] . ":" . $this->_config['proxy-password']);
                }
            }
            $data = curl_exec($ch);
            curl_close($ch);
            if ($isJson) {
                $data = json_decode($data);
            }
            if ($this->cacheEnabled) {
                $this->cache->set($cacheKey, $data, $this->_config['cache-timeout']);
            }
            return $data;
        }
        private function remote_post($url, $body = [], $headers = [], $isJson = true)
        {
            $ch      = curl_init();
            $options = [
                CURLOPT_URL            => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($body),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT      => $this->_config['user-agent'],
                CURLOPT_ENCODING       => "utf-8",
                CURLOPT_AUTOREFERER    => true,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_COOKIEJAR      => $this->_config['cookie_file'],
            ];
            curl_setopt_array($ch, $options);
            if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
            $data = curl_exec($ch);
            curl_close($ch);
            if ($isJson) {
                $data = json_decode($data);
            }
            return $data;
        }
    }
}
