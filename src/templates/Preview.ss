<div id="socialposter-preview" class="form-group field">
    <label class="form__field-label">Preview</label>
    <div class="form__field-holder">
        $PreviewHTML
    </div>
</div>

    
<script defer src="https://use.fontawesome.com/releases/v5.15.4/js/all.js" integrity="sha384-rOA1PnstxnOBLzCLMcre8ybwbTmemjzdNlILg8O7z1lUkLXozs4DHonlDtnE7fpc" crossorigin="anonymous"></script>

<script>
    (function($) {
        $(document).ready(function() {

            var formId = 'Form_ItemEditForm';
            var initialThumbnail = '$Post.Image.ScaleWidth(500).URL';

            var fieldsMap = {
                'Title': '.js-post-preview--title',
                'Content': '.js-post-preview--content'
            }

            function updatePostPreviewField(field) {
                var previewFieldElement = $(fieldsMap[$(field).attr('name')]);
                var value = htmlEntitiesEncode($(field).val()).replaceAll("\\n", '<br>');
                previewFieldElement.html(value);
            }

            function htmlEntitiesEncode(str) {
                var tempElement = document.createElement('div');
                tempElement.innerText = str;
                return tempElement.innerHTML;
            }

            function updateImageFromField() {
                var thumbnail = $('#' + formId + ' .uploadfield[type="file"]').data('state').data.files[0].thumbnail;

                if (thumbnail) {
                    $('.js-post-preview--image').attr('src', thumbnail).show();
                }
            }

       
            for (var field in fieldsMap) {
                if (fieldsMap.hasOwnProperty(field)) {

                    $('#' + formId + ' [name="' + field + '"]').entwine({
                        onkeyup: function(e) { 
                            updatePostPreviewField(this);
                        },
                        onmatch: function(e) { 
                            updatePostPreviewField(this);
                        }
                    });

                }
            }

            $('#' + formId + ' [name="Link"]').entwine({
                onkeyup: function(e) { 
                    if ($(this).val()) {
                        $('.js-post-preview--link').show();
                    } else {
                        $('.js-post-preview--link').hide();
                    }
                },
                onmatch: function(e) { 
                    if ($(this).val()) {
                        $('.js-post-preview--link').show();
                    } else {
                        $('.js-post-preview--link').hide();
                    }
                }
            });


            $('#' + formId + ' .uploadfield-item__thumbnail').entwine({
                onmatch: function(e) {                     
                    var thumbnail = initialThumbnail;

                    if (!thumbnail) {
                        thumbnail = $(this).css('background-image').replace('url("', '').replace('")', '');
                    }

                    if (thumbnail) {
                        $('.js-post-preview--image').attr('src', thumbnail).show();
                        initialThumbnail = null;
                    } else {
                        $('.js-post-preview--image').hide();
                    }
                }
            });
            
            $('#' + formId + ' [name="Image[Files][]"]').entwine({
                onchange: function(e) { 
                    updateImageFromField();
                },
                onmatch: function(e) { 
                    updateImageFromField();
                }
            });

            $('#' + formId + ' .uploadfield-item__remove-btn').entwine({
                onclick: function(e) { 
                    $('.js-post-preview--image').hide();
                }
            });

        });    

    }(jQuery));
    
</script>
