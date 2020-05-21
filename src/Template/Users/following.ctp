<?= $this->element('profile'); ?>
<div class='container-following'>
<?php
    if(!empty($data)) {
        $user = "<div class='container pt-4'>";
        foreach ($data as $following) {
            $fullname = $following->full_name;
            $profilePic = $this->System->getUserPic($following->id);
            $joined = $following->joined;
            $me = $myId === $following->id ? true : false;
            
            $user .= "<div class='container-fluid border'>";

            $user .= "<div class='row'>";
            $user .=         "<div class='p-0 m-2 col-sm-1'>
                                    <img src='".$profilePic."'class='mx-auto'>
                                </div>
                                <div class='row col-sm-11'>
                                    <div class='post-user col-sm-6 mt-3'><a href='".$this->Url->build(['controller' => 'users', 'action' => 'profile', $following->id])."'>
                                    $fullname
                                    </a></div>";
                        if(!$me){
                            $isFollowing = $this->System->isFollowing($myId, $following->id);
                            $hadFollowed = $this->System->hadFollowed($myId, $following->id);
                            $hrefAction = $hadFollowed ? 'unfollow' : 'follow';

                            $btnTitle = $isFollowing ? 'Unfollow' : 'Follow';
                            $btnClass = $isFollowing ? 'unfollow_user btn-danger' : 'follow_user btn-primary';
            $user .=  "<div id='buttons-container' class='follow-button col-sm-5 mt-3'>
                            <button href='".$this->Url->build(['controller' => 'users', 'action' => $hrefAction, 'following_id' => $following->id])."' type='button' class='".$btnClass." btn-sm'>".$btnTitle."</button>
                        </div>";
                        }
            $user .=       "<div class='post-content mb-3 col-sm-12'>
                                        <span>
                                        <h5 class='text-secondary'><i class='far fa-calendar-alt'></i> Joined $joined</h5>
                                    </span>
                                    </div>";
            $user .=      "</div>";
            $user .=    "</div>";
            $user .= "</div>";
        }
        $user .=     "</div>";
        echo $user;

        if($pages > 1) {
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
            } else {
                $page = 1;
            }
            $pagination = "<nav class='paging'>";
                if($page > 1) {
                    $pagination .= "<button class='get_follow_bypage fa fa-fast-backward pl-3' title='first' href='/users/following?".$field."=".$id."&page=1'></button>";
                    $pagination .= "<button class='get_follow_bypage fa fa-arrow-left pl-3' title='previous' href='/users/following?".$field."=".$id."&page=".($page - 1)."'></button>";
                }
                
                if($page < $pages) {
                    $pagination .="<button class='get_follow_bypage fa fa-arrow-right pl-3' title='next' href='/users/following?".$field."=".$id."&page=".($page + 1)."'></button>
                                    <button class='get_follow_bypage fa fa-fast-forward pl-3' title='last' href='/users/following?".$field."=".$id."&page=". $pages ."'></button>";
                }
                $pagination .= "</nav>";
            echo $pagination;
        }
    } else {
        echo "<span class='container'><h2>".$message."</h2></span>";
    }
?>
</div>