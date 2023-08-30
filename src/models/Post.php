<?php

namespace Toast\SocialPoster\Models;

use SilverStripe\Assets\Image;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Forms\CompositeValidator;
use SilverStripe\Forms\GridField\GridField;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use Toast\SocialPoster\Helpers\SocialPoster;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

class Post extends DataObject implements PermissionProvider
{
    use Configurable;

    private static $table_name = 'SocialPoster_Post';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'Text',
        'Link' => 'Text',
        'Commentary' => 'Text',
        'Schedule' => 'Datetime',
        'HasSchedule' => 'Boolean'
    ];

    private static $has_one = [
        'Image' => Image::class,
        'OwnerObject' => SiteTree::class,
    ];

    private static $has_many = [
        'Submissions' => PostSubmission::class
    ];

    private static $belongs_many_many = [
        'Feeds' => Feed::class
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'SubmissionsForSummary' => 'Submitted to feeds',
        'LastEdited.Nice' => 'Last edited'
    ];

    private static $searchable_fields = [
        'Title',
        'Content'
    ];

    private static $owns = [
        'Image'
    ];

    private static $default_sort = [
        'LastEdited' => 'DESC'
    ];

    private $submissionActionRunOnSave = false;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Identifier',
            'OwnerObjectID',
            'Image',
            'Feeds',
            'HasSchedule',
            'Schedule',
            'Submissions'
        ]);

        $fields->addFieldsToTab('Root.Main', [            
            TextareaField::create('Content', 'Content')
                ->setMaxLength(50000),
            TextareaField::create('Commentary', 'Commentary')
                ->setMaxLength(50000),
            TextField::create('Link', 'Link')
                ->setAttribute('placeholder', 'eg. https://www.example.com/articles/test-article'),
            $scheduleField = Wrapper::create(DatetimeField::create('Schedule', 'Schedule')
                ->setReadonly($this->hasSubmissions() && !$this->HasSchedule)
            )
        ]);

        $submitFeedsMap = [];

        $feeds = Feed::get()
            ->filter('Active', true);

        foreach($feeds as $feed) {
            $action = $this->Submissions()->find('FeedID', $feed->ID) ? 'update' : 'create';
            $submitFeedsMap[$feed->ID] = $feed->Title . ' - ' . ucfirst(strtolower($feed->Platform)) . ' (' . $action . ')';
        }

        $fields->addFieldsToTab('Root.Main', [            
            LiteralField::create('', !$this->hasSubmissions() ? '
                <p class="message">Not submitted to any feeds yet. To submit select a feed and click <b>' . ($this->exists() ? 'Save' : 'Create') . '</b>.</p>' : ''
            ),            
            DropdownField::create('SubmitFeedID', 'Select feed to submit post to')
                ->setSource($submitFeedsMap)
                ->setEmptyString('- None (editing draft) -'),
            TextField::create('Title', 'Post title')
        ], 'Content');


        $fields->addFieldsToTab('Root.Main', [
            UploadField::create('Image', 'Image')
                ->setAllowedFileCategories('image')
        ], 'Link');

        
        if (!$this->exists()) {
            $fields = $this->prepopulateFields($fields);

        } else {
            $gridSubmissionsConfig = GridFieldConfig_RelationEditor::create()
                ->removeComponentsByType([
                    GridFieldAddExistingAutocompleter::class,
                    GridFieldDeleteAction::class,
                    GridFieldEditButton::class
                ])
                ->addComponent(GridFieldDeleteAction::create(false));

            $fields->addFieldsToTab('Root.Submissions', [
                GridField::create('Submissions', 'Submissions', $this->Submissions(), $gridSubmissionsConfig)
            ]);

            $fields->addFieldsToTab('Root.Submissions', [
                LiteralField::create('', '<div class="message warning">Removing submissions from here will also remove them from the social media feed.</div>')
            ], 'Submissions');
        }

        // Previews
        foreach($feeds as $feed) {
            $previewTemplates = [];
            $template = 'Preview' . ucfirst(strtolower($feed->Platform));
            
            $fields->insertAfter('SubmitFeedID',
                $previewTemplates[$template] = Wrapper::create(
                    LiteralField::create($template, ArrayData::create([
                        'Feed' => $feed,
                        'Post' => $this,
                        'PreviewHTML' => ArrayData::create([
                            'Feed' => $feed,
                            'Post' => $this                            
                        ])->renderWith($template),
                    ])->renderWith('Preview'))
                )
            );

            $previewTemplates[$template]
                ->displayIf('SubmitFeedID')->isEqualTo($feed->ID);
        }


        $fields->push(HiddenField::create('_isCMS', 'isCMS', 1));

        $errorFeeds = $this->getSessionAuthFeedErrors();

        if ($errorFeeds->count()) {
            $htmlErrorFeeds = [];
            foreach($errorFeeds as $feed) {
                $htmlErrorFeeds[] = '<p>&#8226; ' . $feed->getFullTitle() . ' <a href="' . $feed->getOAuthLink() . '" target="_blank" class="btn btn-default">&#8594; <b>Reconnect</b></a></p>';
            }

            $submittedFeeds = $this->Feeds()
                ->exclude('ID', $errorFeeds->column('ID'));

            $htmlSubmittedFeeds = [];
            foreach($submittedFeeds as $feed) {
                $htmlSubmittedFeeds[] = '<p>&#8226; ' . $feed->getFullTitle() . '</p>';
            }

            $fields->insertBefore('SubmitFeedID',
                LiteralField::create('AuthErrors', '
                    <div class="message error">
                        <p><b>Unable to submit post to one or more feeds due to authentication error. Please reconnect with the feed(s) indicated below and try again.</b></p>
                        <p><i>The social media user must have permissions to manage the feeds for the authentication to succeed.</i></p>
                        ' . implode('', $htmlErrorFeeds) . '
                    </div>
                ' . (count($htmlSubmittedFeeds) ? '
                    <div class="message success good">
                        <p>The post was submitted the following social feed(s).</p>
                        ' . implode('', $htmlSubmittedFeeds) . '
                    </div>
                ' : ''))
            );

        } else {
            if (Controller::curr()->getRequest()->getSession()->get('PostSubmittedToFeeds')) {
                Controller::curr()->getRequest()->getSession()->clear('PostSubmittedToFeeds');
                $fields->insertBefore('SubmitFeedID',
                    LiteralField::create('SuccessSubmission', '
                        <div class="message success good">
                            <p>The post was submitted to all of the selected social feeds.</p>
                        </div>
                    ')
                );
            }

            if ($errorMessages = $this->getSessionGenericError()) {
                $fields->insertBefore('SubmitFeedID',
                    LiteralField::create('SuccessSubmission', '
                        <div class="message error bad">
                            <p>The following error(s) occurred during submission:</p>
                            <p>' . $errorMessages . '</p>
                        </div>
                    ')
                );
            }
        }


        $logics = [
            'Title' => $fields->dataFieldByName('Title')->displayIf('SubmitFeedID')->isEmpty(),
            'Content' => $fields->dataFieldByName('Content')->displayIf('SubmitFeedID')->isEmpty(),
            'Image' => $fields->dataFieldByName('Image')->displayIf('SubmitFeedID')->isEmpty(),
            'Schedule' => $scheduleField->displayIf('SubmitFeedID')->isEmpty(),
            'Link' => $fields->dataFieldByName('Link')->displayIf('SubmitFeedID')->isEmpty(),
            'Commentary' => $fields->dataFieldByName('Commentary')->displayIf('SubmitFeedID')->isEmpty(),
        ];

        foreach (Feed::platformConfig() as $platform => $config) {

            $hasSubmissionsForFeed = (bool)$this->Submissions()
                ->filter('Feed.Platform', strtolower($platform))
                ->count();

            foreach($config['post_fields'][$hasSubmissionsForFeed ? 'update' : 'create'] as $field) {

                $feedId = Feed::get()
                    ->filter([
                        'Platform' => strtolower($platform),
                        'Active' => true
                    ])
                    ->first()
                    ->ID;
                
                $logics[$field]->orIf('SubmitFeedID')->isEqualTo($feedId);

            }

        }

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // submit post if saving from the CMS
        if (Controller::has_curr()) {
            if ($request = Controller::curr()->getRequest()) {
                if ($request->requestVar('_isCMS')) {
                    if (!$this->submissionActionRunOnSave) {
                        if ($feedId = $request->requestVar('SubmitFeedID')) {

                            if (!$this->HasSchedule && $this->Schedule) {
                                $this->HasSchedule = true;
                            }      
                            
                            $feed = Feed::get()->byID($feedId);

                            $hasSubmissionsForFeed = (bool)$this->Submissions()
                                ->filter('FeedID', $feed->ID)
                                ->count();

                            $this->submitToSocialMedia($feed, $hasSubmissionsForFeed);
                            $this->submissionActionRunOnSave = true;
                        }
                    }
                }
            }
        }
    }

    public function getSubmissionsForSummary()
    {
        $feeds = [];
        foreach($this->Submissions() as $submission) {
            $feeds[] = $submission->Feed()->getFullTitle();
        }

        return implode(', ', $feeds);
    }

    public function submitToSocialMedia($feed, $update = false)
    {
        $hasFailedSubmission = false;
        $errorMessage = null;

        $params = [
            'message' => $this->Content
        ];

        if ($this->Title) {
            $params['title'] = $this->Title;
        }

        if ($this->Link) {
            $params['link'] = $this->Link;
        }

        if ($this->Schedule) {
            $params['scheduled_publish_time'] = strtotime($this->Schedule);
        }

        if ($this->Commentary) {
            $params['commentary'] = $this->Commentary;
        }

        if ($this->ImageID) {
            if ($image = Image::get()->byID($this->ImageID)) {
                $params['image_url'] = $image->AbsoluteLink();
            }

        } else {
            $params['image_url'] = null;

        }

        $this->extend('updateSocialMediaRequestParams', $params, $feed, $update);

        if ($update) {
            
            if ($submission = $this->Submissions()->find('FeedID', $feed->ID)) {
                // update post
                $result = SocialPoster::updatePost($submission->Identifier, $feed->FeedId, $params);

                if (isset($result['error'])) {
                    $errorMessage = '<b>' . ucfirst($feed->Platform) . ':</b> ' . (isset($result['error']['message']) ? $result['error']['message'] : 'Unknown error');
                    $hasFailedSubmission = true;

                } else {

                    if (SocialPoster::isAuthError($feed->Platform)) {
                                                
                        $this->setSessionAuthErrorForFeed($feed);
                        $hasFailedSubmission = true;

                        // mark feed as unauthenticated
                        $feed->Authenticated = false;
                        $feed->write();

                    } elseif ($result) {
                        $submission->LastSubmitted = date('Y-m-d H:i:s');
                        $submission->write();

                        // ensure authentication status
                        $feed->Authenticated = true;
                        $feed->write();
                    }

                }

            }
            
        } else {

            // check if not yet submitted to this feed
            if (!$this->submittedToFeed($feed)) {
                // create post
                $result = SocialPoster::createPost($feed->FeedId, $params);                    

                if (!is_array($result) && $result) {
                    // register submission
                    $this->Submissions()->add(PostSubmission::create([
                        'Identifier' => $result,
                        'FeedID' => $feed->ID,
                        'LastSubmitted' => date('Y-m-d H:i:s')
                    ]));

                    // ensure authentication status
                    $feed->Authenticated = true;
                    $feed->write();

                } else {

                    if (SocialPoster::isAuthError($feed->Platform)) {
                        $this->setSessionAuthErrorForFeed($feed);

                        // mark feed as unauthenticated
                        $feed->Authenticated = false;
                        $feed->write();

                    } else {
                        $errorMessage = '<b>' . ucfirst($feed->Platform) . ':</b> ' . (isset($result['error']['message']) ? $result['error']['message'] : 'Unknown error');
                    }

                    $hasFailedSubmission = true;
                }
            }
        }


        // set session flag for successful submission field
        if (!$hasFailedSubmission) {
            if (Controller::has_curr()) {
                if ($request = Controller::curr()->getRequest()) {
                    if ($session = $request->getSession()) {
                        $session->set('PostSubmittedToFeeds', true);
                    }
                }
            }
        }

        if ($errorMessage) {
            $this->setSessionGenericError($errorMessage);
        }
    }

    public function getCMSCompositeValidator(): CompositeValidator
    {
        $validator = parent::getCMSCompositeValidator();

        $fields = [
            'Content'
        ];

        /*
            We'll make Schedule required if previously submitted to Facebook with a schedule value
        */
        if ($this->HasSchedule) {
            // @TODO: make this configurable
            if ($this->Submissions()->filter('Feed.Platform', ['facebook', 'instagram'])->count()) {
                $fields[] = 'Schedule';
            }
        }

        $validator->addValidator(RequiredFields::create($fields));

        $this->extend('updateCMSCompositeValidator', $validator);

        return $validator;
    }

    public function prepopulateFields($fields)
    {
        if ($this->OwnerObjectID) {
            if ($object = SiteTree::get()->byID($this->OwnerObjectID)) {
                $objectClass = $object->ClassName;

                if ($parent = $objectClass::get()->byID($object->ID)) {
                    $parentData = $parent->getPagePostData();
                    $fields->dataFieldByName('Title')->setValue($parent->Title);

                    foreach($parentData as $key => $value) {

                        if ($key == 'Image') {
                            if ($image = Image::get()->byID($value)) {                                
                                $this->ImageID = $image->ID;
                            }    
                        }

                        $fields->dataFieldByName($key)->setValue($value);
                    }
                }
            }
        }

        // embed link in content
        if ($link = $fields->dataFieldByName('Link')->Value()) {
            $fields->dataFieldByName('Content')->setValue($fields->dataFieldByName('Content')->Value() . "\r\n\r\n" . $link);
        }

        $this->extend('updatePrepopulateFields', $fields);

        return $fields;
    }

    public function submittedToFeed($feed)
    {
        return (bool)$this->Submissions()->find('FeedID', $feed->ID);
    }

    public function hasSubmissions()
    {
        return (bool)$this->Submissions()->count();
    }

    public function setSessionGenericError($message)
    {
        if (Controller::has_curr()) {
            if ($request = Controller::curr()->getRequest()) {
                $request->getSession()->set('SocialPosterGenericError', $message);
            }
        }
    }

    public function getSessionGenericError()
    {
        if (Controller::has_curr()) {
            if ($request = Controller::curr()->getRequest()) {
                $message = $request->getSession()->get('SocialPosterGenericError');
                $request->getSession()->clear('SocialPosterGenericError');
                return $message;
            }
        }
    }

    public function setSessionAuthErrorForFeed($feed)
    {
        if (Controller::has_curr()) {
            if ($request = Controller::curr()->getRequest()) {
                $request->getSession()->set('SocialPosterAuthErrorFeeds.' . $feed->ID, true);
            }
        }
    }

    public function getSessionAuthFeedErrors()
    {
        $output = ArrayList::create();

        if (Controller::has_curr()) {
            if ($request = Controller::curr()->getRequest()) {

                if ($feeds = $request->getSession()->get('SocialPosterAuthErrorFeeds')) {
                    foreach($feeds as $id => $isError) {
                        if ($isError) {
                            if ($feed = Feed::get()->byID($id)) {
                                $output->push($feed);
                            }
                        }
                    }
                }

                $request->getSession()->clear('SocialPosterAuthErrorFeeds');
            }
        }

        return $output;
    }

    public function canEdit($member = null)
    {
        return Permission::check('MANAGE_SOCIAL_POSTS');
    }

    public function canDelete($member = null)
    {
        if ($this->hasSubmissions()) {
            return false;
        }

        return Permission::check('MANAGE_SOCIAL_POSTS');
    }

    public function providePermissions()
    {
        return [
            'MANAGE_SOCIAL_POSTS' => 'Manage Social Posts'
        ];
    }


}
