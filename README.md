# Social Poster
Social Poster API service integration.

Get in touch with https://toast.co.nz to get an access token.

## Requirements
See composer.json

## Installation
```
composer require toastnz/socialposter
```

Set up field mapping
```yml

Page:
  # This will add a tab to the page in the CMS
  extensions:
    - Toast\SocialPoster\Extensions\SocialPosterExtension

  # Mapping is optional and will be used to pre-populate the fields in the CMS
  social_poster:
    fields:
      Title: Title
      Content: PageSummary
      Image: SummaryImage
      Link: AbsoluteLink
      Schedule: PublishDate

# Required
# Alternatively you can set SOCIAL_POSTER_TOKEN in your .env file
Toast\SocialPoster\Helpers\SocialPoster:
  access_token: YOUR-ACCESS-TOKEN-HERE

```
