<?= $this->element('profile'); ?>

<?php
    $id = $myId;
    $userId = $profile->id;
    $profilePic = $profile->profile_image;
    $profileFullName = $profile->full_name;
?>
<div id="profile-post-container">
    <?php
        if(isset($data)) {
            $article = '';
            foreach ($data as $key => $post) {
                $profilePic = $post->user->profile_image;
                $postAgo = $post->post_ago;
                $postId = $post->id;
                $postUserId = $post->user_id;
                $myPost = $postUserId === $id ? true : false ;
                
                $likeHrefAction = $post->liked_before ? 'delete' : 'add';
                
                $classListener = $post->deleted ? 'restore_post fas fa-recycle' : 'delete_post fa fa-trash';
                $title = $post->deleted ? 'Restore' : 'Delete';
                
                $article .= "<div class='post-container border'>";
                $article .= "   <div class='row'>
                                    <div class='post-img col-sm-2'>";
                $article .=     "<img src='$profilePic'>";
                $article .= "   </div>";

                $article .= "<div class='post-details col-sm-10'>
                                <div class='row'>";
                            if($myPost) {
                            $article .= "<div class='system-action-buttons col-sm-12'>
                                            <button class='ml-2'>
                                                <span href='".$this->Url->build(['controller' => 'posts', 'action' => 'edit', $postId])."' class='edit_post fa fa-edit' data-toggle='tooltip' data-placement='top' title='Edit' type='button'></span> 
                                            </button>
                                            <button class=''>
                                                <span href='".$this->Url->build(['controller' => 'posts', 'action' => 'delete', $postId])."' class='".$classListener."' data-toggle='tooltip' data-placement='top' title='".$title."' type='button'></span> 
                                            </button>
                                        </div>";
                            }
                $article .=         "<div class='post-user'><a href='".$this->Url->build(['controller' => 'users', 'action' => 'profile', 'id' => $userId])."'>"
                                        .$profileFullName.
                                    "</a></div>
                                    <div class='post-ago'>
                                        $postAgo
                                    </div>
                                    <div class='post-content col-sm-12'>
                                        <p>".h($post->content, false). "<p>
                                    </div>";
                    if($post->image) {
                        $article .=  "<div class='post-image col-sm-12 mb-2'>
                                        <img src='/".$post->image."'>
                                    </div>";
                    }

                            if($post->post_id) {
                                $sharedPost = $post->shared_post;
                                if($sharedPost) {
                                    $sharedFullName =  $sharedPost->user->full_name;
                                    $sharedProfile =  $sharedPost->user->profile_image;
                                    $sharedPostAgo = $sharedPost->post_ago;
                                    $sharedContent = h($sharedPost->content);
                                    
                                    $sharePost = "<div class='share-post border p-3 m-2'>";
                                    
                                    $sharePost .= "   <div class='row'>
                                                        <div class='post-img col-sm-2'>";
                                    $sharePost .=     "<img src='$sharedProfile'>";
                                    $sharePost .= "   </div>";
    
                                    $sharePost .= "<div class='post-details col-sm-10'>
                                                        <div class='row'>
                                                            <div class='post-user'><a href='".$this->Url->build(['controller' => 'users', 'action' => 'profile', 'id' => $sharedPost->user_id])."'>"
                                                                .$sharedFullName.
                                                            "</a></div>
                                                            <div class='post-ago'>
                                                                $sharedPostAgo
                                                            </div>
                                                            <div class='post-content col-sm-12'>
                                                                <p>".$sharedContent. "<p>
                                                            </div>";
                                            if($sharedPost->image) {
                                                $sharePost .=  "<div class='post-image col-sm-12 mb-2'>
                                                                <img src='/".$sharedPost->image."'>
                                                            </div>";
                                            }
                                    $sharePost .=      "</div>
                                                    </div>
                                                    </div>
                                                </div>";
                                } else {
                                    $sharePost = "<div class='share-post border p-3 m-2'>";
                                    $sharePost .= "<span><h4> Post Deleted </h4></span>";
                                    $sharePost .= "</div>";
                                }
                                $article .= $sharePost;
                            }

                $article  .=  "</div>
                            </div>
                        </div>";
                $buttons = "<div class='post-buttons border-top'>
                                <div class='row'>
                                    <button href='".$this->Url->build(['controller' => 'comments', 'action' => 'add', $postId])."' class='comment_post col-sm-3'>
                                        <span class='" . ($post->is_commented ? 'fas' : 'far') ." fa-comment' data-toggle='tooltip' data-placement='top' title='Comment'> ". (!empty($post->comment_count) ? $post->comment_count : '')."</span>
                                    </button>
                                    <button href='".$this->Url->build(['controller' => 'likes', 'action' => $likeHrefAction, $postId])."' class='like_post col-sm-3'>
                                        <span class='" . ($post->is_liked ? 'fas' : 'far') ." fa-heart' data-toggle='tooltip' data-placement='top' title='Like'> ". (!empty($post->like_count) ? $post->like_count : '') ."</span>
                                    </button>
                                    <button href='".$this->Url->build(['controller' => 'posts', 'action' => 'share', $postId])."' class='share_post col-sm-3'>
                                        <span class='" . ($post->is_shared ? 'fas' : 'far') ." fa-share-square' data-toggle='tooltip' data-placement='top' title='Share'> ". (!empty($post->share_count) ? $post->share_count : '')  ."</span>
                                    </button>
                                    <a href='".$this->Url->build(['controller' => 'posts', 'action' => 'view', $postId])."' class='col-sm-3'>
                                        <span class='fa fa-eye' data-toggle='tooltip' data-placement='top' title='View post'></span>
                                    </a>
                                </div>
                            </div>";
                $article .= $buttons;
                $article .= "</div>";
            }
            echo $article;

            if($pages > 1) {
                if (isset($_GET['page'])) {
                    $page = $_GET['page'];
                } else {
                    $page = 1;
                }
                $pagination = "<nav class='paging'>";
                    if($page > 1) {
                        $pagination .= "<a class='fa fa-fast-backward pl-3' title='first' href='?id=".$id."&page=1'></a>";
                        $pagination .= "<a class='fa fa-arrow-left pl-3' title='previous' href='?id=".$id."&page=".($page - 1)."'></a>";
                    }
                    
                    if($page < $pages) {
                        $pagination .="<a class='fa fa-arrow-right pl-3' title='next' href='?id=".$id."&page=".($page + 1)."'></a>
                                        <a class='fa fa-fast-forward pl-3' title='last' href='?id=".$id."&page=". $pages ."'></a>";
                    }
                    $pagination .= "</nav>";
                echo $pagination;
            }
        }
    ?>
</div>