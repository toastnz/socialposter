<?php

namespace Toast\SocialPoster\Extensions;

use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use Toast\SocialPoster\Models\Post;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

class SocialPosterExtension extends DataExtension 
{

    private static $has_many = [
        'SocialPosts' => Post::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $postGridConfig = GridFieldConfig_RecordEditor::create()
            ->removeComponentsByType(GridFieldAddExistingAutocompleter::class);

        $fields->addFieldsToTab('Root.SocialPoster', [
            GridField::create('SocialPosts', 'Submitted posts', $this->owner->SocialPosts(), $postGridConfig)
        ]);
        
    }


    public function getPagePostData()
    {
        $output = [];

        if ($config = $this->owner->config()->social_poster) {

            if (isset($config['fields']) && is_array($config['fields'])) {

                foreach ($config['fields'] as $internalField => $classField) {
                    
                    if ($classField) {

                        $value = $this->owner->hasMethod($classField) ? $this->owner->$classField() : $this->owner->$classField;

                        // check image object
                        if ($internalField == 'Image') {
                            $value = ($value instanceof Image) && $value->exists() ? $value->ID : null;
                        }

                        // remove link if not a valid URL
                        if ($internalField == 'Link') {
                            $value = filter_var($value, FILTER_VALIDATE_URL) === FALSE ? null : $value;
                        }

                        $output[$internalField] = $value;
                    }
                }
            }
        }

        return $output;
    }

}