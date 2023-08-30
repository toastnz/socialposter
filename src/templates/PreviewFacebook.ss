<style>
    .fb-post {
        background-color: #fff;
        max-width: 500px;
        border: 1px solid #ccc;
        padding: 10px;
      }
      
      .fb-post-header {
        display: flex;
        align-items: center;
      }
      
      .fb-post-header img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        margin-right: 10px;
      }
      
      .fb-post-header h3 {
        margin: 0;
      }
      
      .fb-post-header p {
        margin: 0;
        font-size: 12px;
        color: #777;
      }
      
      .fb-post-body {
        margin-top: 10px;
      }
      
      .fb-post-body img {
        width: 100%;
      }
      
      .fb-post-footer {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
      }
      
      .fb-post-footer-icons {
        display: flex;
      }

      .fb-post-footer-icons > svg {
        margin-right: 15px;
      }
      
      .fb-post-footer-icons i {
        margin-right: 10px;
        font-size: 16px;
        color: #777;
      }
      
      .fb-post-footer-text {
        font-size: 12px;
        color: #777;
      }
</style>

<div class="fb-post">
    <div class="fb-post-header">
      <img src="https://via.placeholder.com/50x50" alt="Profile picture">
      <div class="fb-post-header-text">
        <h3>$Feed.Title</h3>
        <p>Posted on March 26, 2023</p>
      </div>
    </div>
    <div class="fb-post-body">
      <p class="js-post-preview--content">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis eu mauris eget lectus fermentum euismod. Sed ultrices erat at lorem gravida aliquet. Phasellus sit amet faucibus tellus. Sed tristique magna id sapien imperdiet, id mattis velit venenatis. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Donec at diam sit amet diam tincidunt aliquam quis sed est.</p>
      <img src="https://via.placeholder.com/500x300" alt="Post image" class="js-post-preview--image" style="display:none">
    </div>
    <div class="fb-post-footer">
      <div class="fb-post-footer-icons">
        <i class="far fa-thumbs-up"></i>
        <i class="far fa-comment"></i>
        <i class="far fa-share-square"></i>
      </div>
      <div class="fb-post-footer-text">
        <p>42 likes · 10 comments · 5 shares</p>
      </div>
    </div>
</div>

