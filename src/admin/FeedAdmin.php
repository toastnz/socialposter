<?php

namespace Toast\SocialPoster\Admin;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use Toast\SocialPoster\Models\Feed;
use Toast\SocialPoster\Models\Post;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldViewButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Security\PermissionProvider;
use Toast\SocialPoster\Helpers\SocialPoster;

class FeedAdmin extends ModelAdmin implements PermissionProvider
{
    private static $url_segment = 'social-feeds';

    private static $menu_title = 'Social Feeds';

    public $showImportForm = false;

    private static $managed_models = [
        Feed::class,
        Post::class
    ];

    private static $allowed_actions = [
        'syncfeeds'
    ];


    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $gridField = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));

        if ($this->modelClass == Feed::class) {
            $gridField->getConfig()
                ->removeComponentsByType([
                    GridFieldAddNewButton::class,
                    GridFieldDeleteAction::class,
                    GridFieldEditButton::class,
                    GridFieldExportButton::class,
                    GridFieldPrintButton::class
                ])
                ->addComponent(GridFieldViewButton::create());
        }

        if ($this->modelClass == Feed::class) {
            $syncEndpoint = Controller::join_links('admin', self::$url_segment, $gridField->getName(), 'syncfeeds');

            $form->Fields()
                ->insertBefore($gridField->getName(),
                    LiteralField::create('FeedHelp', '
                        <a href="#" class="btn btn-primary" onclick="event.preventDefault(); this.style.cssText=\'pointer-events:none\'; this.innerHTML=\'Please, wait...\'; fetch(\'' . $syncEndpoint . '\').then((data) => { location.reload() });">Refresh feeds</a>
                    ')
                );
        }

        return $form;
    }

    public function syncfeeds(HTTPRequest $request)
    {
        SocialPoster::syncFeed();
    }
 
    public function providePermissions()
    {
        return [
            'CMS_ACCESS_FeedAdmin' => [
                'name' => 'Access to Social Feeds section',
                'category' => 'CMS Access'
            ]
        ];
    }
    
}