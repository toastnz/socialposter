<style>
    .insta-post {
        background-color: #fff;
        max-width: 500px;
        border: 1px solid #ccc;
        padding: 10px;
      }
      
      .insta-post-header {
        display: flex;
        align-items: center;
      }
      
      .insta-post-header img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        margin-right: 10px;
      }
      
      .insta-post-header h3 {
        margin: 0;
      }
      
      .insta-post-body {
        margin-top: 10px;
      }
      
      .insta-post-body img {
        width: 100%;
      }
      
      .insta-post-footer {
        margin-top: 10px;
      }
      
      .insta-post-footer-icons {
        display: flex;
      }

      .insta-post-footer-icons > svg {
        margin-right: 15px;
      }
      
      .insta-post-footer-icons i {
        margin-right: 10px;
        font-size: 16px;
        color: #777;
      }
      
      .insta-post-footer-likes p {
        font-size: 12px;
        color: #777;
        margin-top: 5px;
      }
      
      .insta-post-footer-caption {
        margin-top: 10px;
      }
      
      .insta-post-footer-caption h4 {
        margin: 0;
      }
      
      .insta-post-footer-comments {
        margin-top: 10px;
      }
      
      .insta-post-footer-comment {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
      }
      
      .insta-post-footer-comment h4 {
        margin: 0;
        margin-right: 10px;
      }
      
      .insta-post-footer-time {
        margin-top: 10px;
      }
      
      .insta-post-footer-time p {
        font-size: 12px;
        color: #777;
      }
</style>

<div class="insta-post">
    <div class="insta-post-header">
      <img src="https://via.placeholder.com/50x50" alt="Profile picture">
      <h3>$Feed.Title</h3>
    </div>
    <div class="insta-post-body">
      <img src="https://via.placeholder.com/500x500" alt="Post image" class="js-post-preview--image" style="display:none">
    </div>
    <div class="insta-post-footer">
      <div class="insta-post-footer-icons">
        <i class="far fa-heart"></i>
        <i class="far fa-comment"></i>
        <i class="far fa-paper-plane"></i>
      </div>
      <div class="insta-post-footer-likes">
        <p>42 likes</p>
      </div>
      <div class="insta-post-footer-caption">
        <h4>$Feed.Title</h4>
        <p class="js-post-preview--content">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis eu mauris eget lectus fermentum euismod. Sed ultrices erat at lorem gravida aliquet.</p>
      </div>
      <div class="insta-post-footer-time">
        <p>Posted 3 hours ago</p>
      </div>
    </div>
</div>