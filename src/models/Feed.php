<?php

namespace Toast\SocialPoster\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\PermissionProvider;
use Toast\SocialPoster\Helpers\SocialPoster;

class Feed extends DataObject implements PermissionProvider
{
    private static $table_name = 'SocialPoster_Feed';

    private static $db = [
        'Title' => 'Varchar(255)',
        'FeedId' => 'Varchar(255)',
        'FeedLink' => 'Text',
        'Platform' => 'Varchar(255)',
        'Active' => 'Boolean',
        'Authenticated' => 'Boolean'
    ];

    private static $many_many = [
        'Posts' => Post::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'FeedId' => 'Feed ID',
        'Platform' => 'Platform',
        'Active.Nice' => 'Active'
    ];

    private static $platform_config = [
        'Facebook' => [
            'post_fields' => [
                'create' => [
                    'Content',
                    'Link',
                    'Schedule',
                    'Image'
                ],
                'update' => [
                    'Content',
                    'Link',
                    'Schedule',
                    'Image'
                ]
            ]
        ],
        'Instagram' => [
            'post_fields' => [
                'create' => [
                    'Content',
                    'Link',
                    'Schedule',
                    'Image'
                ],
                'update' => [
                    'Content',
                    'Link',
                    'Schedule',
                    'Image'
                ]
            ]
        ],
        'LinkedIn' => [
            'post_fields' => [
                'create' => [
                    'Title',
                    'Content',
                    'Link',
                    'Image',
                    'Commentary'
                ],
                'update' => [
                    'Commentary'
                ]
            ]
        ],

    ];

    public function getFullTitle()
    {
        return $this->Title . ' (' . ucfirst($this->Platform) . ')';
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Posts',
            'Active'
        ]);

        if ($this->exists()) {
            $postGridConfig = GridFieldConfig_RecordEditor::create()
                ->removeComponentsByType([
                    GridFieldAddExistingAutocompleter::class
                ]);

            $fields->addFieldsToTab('Root.Posts', [
                GridField::create('Posts', 'Posts', $this->Posts(), $postGridConfig)
            ]);

            //if (!$this->Authenticated) {

                $fields->addFieldsToTab('Root.Main', [
                    LiteralField::create('AuthenticateButton', '
                        <div class="message warning">
                            <p><b>Re-connect with ' . ucfirst($this->Platform) . '</b></p>
                            <p><i>The social media user must have permissions to manage this feed.</i></p>                            
                            <p><a href="' . $this->getOAuthLink() . '" class="btn btn-primary" target="_blank">Connect</a></p>
                        </div>
                        <p><i>Save this feed to refresh the authentication status after connecting.</i></p>

                    ')
                ]);

            //}
        }

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->isChanged('Authenticated')) {
            $this->syncAuthState();
        }
    }
    
    public function getOAuthLink()
    {
        $link = SocialPoster::getOAuthLink($this->Platform, $this->FeedId);
        
        $this->extend('updateOAuthLink', $link);

        return $link;
    }

    public function syncAuthState($write = false)
    {
        if (SocialPoster::getFeed($this->FeedId)) {
            $this->Authenticated = true;

        } else {
            if (SocialPoster::isAuthError($this->Platform)) {
                $this->Authenticated = false;
            }
        }
        
        if ($write) {
            $this->write();
        }
    }

    public function hasNativeScheduleSupport()
    {
        return in_array($this->Platform, [
            'facebook',
            'instagram'
        ]);
    }

    public static function platformConfig($all = false)
    {
        if ($all) {
            return self::$platform_config;
        }

        $output = [];

        foreach(self::$platform_config as $platform => $config) {
            if (in_array(strtolower($platform), self::get()->filter('Active', true)->column('Platform'))) {
                $output[$platform] = $config;
            }
        }

        return $output;        
    }

    public function providePermissions()
    {
        return [
            'MANAGE_SOCIAL_FEEDS' => 'Manage Social Feeds'
        ];
    }

    public function canCreate($member = null, $context = [])
    {
        return false;
    }


}
