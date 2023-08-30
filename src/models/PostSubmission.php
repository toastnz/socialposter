<?php

namespace Toast\SocialPoster\Models;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\FieldType\DBField;
use Toast\SocialPoster\Helpers\SocialPoster;

class PostSubmission extends DataObject
{
    private static $table_name = 'SocialPoster_PostSubmission';

    private static $db = [
        'Identifier' => 'Varchar(255)',
        'LastSubmitted' => 'Datetime'
    ];

    private static $has_one = [
        'Feed' => Feed::class,
        'Post' => Post::class
    ];

    private static $summary_fields = [
        'Feed.Title' => 'Feed',
        'LastSubmitted.Nice' => 'Last submitted on',
        'ScheduleForSummary' => 'Publish date'
    ];

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();

        if (SocialPoster::deletePost($this->Identifier, $this->Feed()->FeedId)) {
            if (!$this->Post()->hasSubmissions()) {
                $this->Post()->HasSchedule = false;
                $this->Post()->write();
            }
        }
    }

    public function getScheduleForSummary()
    {
        if ($feed = $this->Feed()) {
            if ($feed->hasNativeScheduleSupport()) {
                if ($post = $this->Post()) {
                    return DBField::create_field('Datetime', $post->Schedule ?: $this->LastSubmitted)->Nice();
                }        
            }
        }

        return DBField::create_field('Datetime', $this->LastSubmitted)->Nice();
    }

    public function canDelete($member = null)
    {
        return Permission::check('MANAGE_SOCIAL_POSTS');
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

}