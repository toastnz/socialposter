<?php

namespace Toast\SocialPoster\Helpers;

use GuzzleHttp\Psr7\Stream;
use SilverStripe\Control\Director;
use Toast\SocialPoster\Models\Feed;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;

class SocialPoster
{
    use Injectable;
    use Configurable;

    private static $access_token;

    protected static $last_error;

    protected static $api_base_url = 'https://socialposter-api.toastnz.io';

    public static function getFeed($feedId = null)
    {
        $params = [];

        if ($feedId) {
            $params['feed_id'] = $feedId;
        }

        if ($response = self::_call('GET', 'feed', count($params) ? $params : null)) {
            if (isset($response['data'])) {
                return $response['data'];
            }
        }
    }

    public static function getPost($feedId, $scheduled = false, $postId = null)
    {
        $feed = self::getActiveFeed($feedId);

        if ($feed && $feed->Platform) {
            $requestData = [
                'feed_id' => $feed->FeedId,
                'type' => $scheduled ? 'scheduled' : 'published'
            ];

            if ($postId) {
                $requestData['post_id'] = $postId;
            }

            $response = self::_call('GET', $feed->Platform . '/post', $requestData);

            if ($response) {
                if (isset($response['data'])) {
                    return $response['data'];
                }
            }

        }
    }


    public static function createPost($feedId, $data)
    {
        $feed = self::getActiveFeed($feedId);

        if ($feed && $feed->Platform) {

            $requestData = array_merge($data, [
                'feed_id' => $feed->FeedId
            ]);

            $response = self::_call('POST', $feed->Platform . '/post', $requestData);
            
            if ($lastError = self::lastError()) {
                return [
                    'error' => $lastError
                ];
            }

            if ($response) {
                if (isset($response['data']) && isset($response['data']['post_id'])) {
                    return $response['data']['post_id'];
                }
            }

        }
    }

    public static function updatePost($postId, $feedId, $data)
    {
        $feed = self::getActiveFeed($feedId);

        if ($feed && $feed->Platform) {

            $requestData = array_merge($data, [
                'post_id' => $postId,
                'feed_id' => $feed->FeedId
            ]);

            $response = self::_call('PATCH', $feed->Platform . '/post', $requestData);

            if ($lastError = self::lastError()) {
                return [
                    'error' => $lastError
                ];
            }

            if ($response) {
                return isset($response['status']) && ($response['status'] == 'ok');
            }

        }
    }

    public static function deletePost($postId, $feedId)
    {
        $feed = self::getActiveFeed($feedId);

        if ($feed && $feed->Platform) {

            $requestData = [
                'post_id' => $postId,
                'feed_id' => $feed->FeedId
            ];

            $response = self::_call('DELETE', $feed->Platform . '/post', $requestData);

            if ($response) {
                return isset($response['status']) && ($response['status'] == 'ok');
            }

        }
    }


    public static function syncFeed()
    {
        if ($feed = self::getFeed()) {
            $importedFeedIds = [];

            foreach ($feed as $item) {
                $feed = Feed::get()->find('FeedId', $item['id']);

                if (!$feed) {
                    $feed = Feed::create();
                    $feed->FeedId = $item['id'];
                }

                $feed->Title = $item['title'];
                $feed->FeedLink = $item['link'];
                $feed->Platform = $item['platform'];
                $feed->Active = true;
                $feed->write();

                $importedFeedIds[] = $feed->FeedId;
            }

            // disable missing feeds
            $missingFeeds = Feed::get()
                ->exclude('FeedId', $importedFeedIds);

            foreach ($missingFeeds as $missingFeed) {
                $missingFeed->Active = false;
                $missingFeed->write();
            }

        }
    }    

    public static function getActiveFeed($feedId, $skipSync = false)
    {
        $feed = Feed::get()
            ->filter([
                'FeedId' => $feedId,
                'Active' => true
            ])
            ->first();

        if (!$feed) {
            if (!$skipSync) {
                self::syncFeed();
                return self::getActiveFeed($feedId, true);
            }
        }

        return $feed;
    }


    public static function getAccount()
    {
        $response = self::_call('GET', '/account');

        if ($response) {
            if (isset($response['status'])) {
                return $response['status'] == 'ok';
            }
        }

    }

    private static function _call($method = 'GET', $endpoint = null, $data = null)
    {
        $apiBaseURL = Environment::getEnv('SOCIAL_POSTER_API_BASE_URL');
        $url = Controller::join_links($apiBaseURL ?: self::$api_base_url, $endpoint) . '?access_token=' . self::config()->access_token;

        if ($data) {
            $url .= '&' . http_build_query($data);
        }

        self::$last_error = null;
        $e = null;

        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request(strtoupper($method), $url, ['verify' => !Director::isDev()]);
            return json_decode($response->getBody(), true);

        } catch (\GuzzleHttp\Exception\ServerException $e) {
            self::$last_error = $e->getResponse()->getBody();
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            self::$last_error = $e->getMessage();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            self::$last_error = $e->getResponse()->getBody();
        } catch (\Exception $e) {
            self::$last_error = $e->getMessage();
        } catch (\Throwable $e) {
            self::$last_error = $e->getMessage();
        }
    }

    public static function lastError()
    {
        if (self::$last_error) {
            $error = json_decode(self::$last_error, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $error;
            }

            if (self::$last_error instanceof Stream) {
                self::$last_error = self::$last_error->getContents();
            }

            return [
                'message' => self::$last_error ?: 'Unknown error'
            ];
        }
    }    


    public static function findByKey($items, $key, $id)
    {
        foreach ($items as $item) {
            if ($item[$key] == $id) {
                return $item;
            }
        }
        
        return false;
    }

    public static function getOAuthLink($platform, $feedId)
    {
        $apiBaseURL = Environment::getEnv('SOCIAL_POSTER_API_BASE_URL') ?: self::$api_base_url;
        return Controller::join_links($apiBaseURL, $platform, 'auth') . '?access_token=' . self::config()->access_token . '&feed_id=' . $feedId;
    }

    public static function isAuthError($platform)
    {
        // @TODO: add LinkedIn
        if ($platform == 'facebook' || $platform == 'instagram') {
            $lastError = self::lastError();

            if (isset($lastError['message']) && isset($lastError['message']['error']) && isset($lastError['message']['error']['type'])) {
                return stristr($lastError['message']['error']['message'], 'auth');
            }
        }

        return false;
    }

}


