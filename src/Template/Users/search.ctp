<div class="container default-tab pt-4">
<?php
    if(!empty($data)) {
        $user = "<div class='container pt-4'>";
        foreach ($data as $val) {
            $fullname = $val->full_name;
            $joined = $val->joined;
            $me = $myId === $val->id ? true : false;
            
            $user .= "<div class='container-fluid border'>";
            $user .= "<div class='row'>";
            $user .=         "<div class='p-0 m-2 col-sm-1'>
                                    <img src='".$val->profile_image."'class='mx-auto'>
                                </div>
                                <div class='row col-sm-11'>
                                    <div class='post-user col-sm-6 mt-3'><a href='".$this->Url->build(['controller' => 'users', 'action' => 'profile', $val->id])."'>
                                    $fullname
                                    </a></div>";
                            if(!$me) {
                            $isFollowing = $this->System->isFollowing($myId, $val->id);
                            $hadFollowed = $this->System->hadFollowed($myId, $val->id);
                            $hrefAction = $hadFollowed ? 'unfollow' : 'follow';
                            
                            $btnTitle = $isFollowing ? 'Unfollow' : 'Follow';
                            $btnClass = $isFollowing ? 'unfollow_user btn-outline-danger' : 'follow_user btn-outline-primary';
            $user .=        "<div id='buttons-container' class='follow-button col-sm-5 mt-3'>
                                <button href='".$this->Url->build(['controller' => 'users', 'action' => $hrefAction, $val->id])."' type='button' class='".$btnClass." btn-sm'>".$btnTitle."</button>
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
                    $pagination .= "<a class='fa fa-fast-backward pl-3' title='first' href='/users/search?user=".$searchedUser."&page=1'></a>";
                    $pagination .= "<a class='fa fa-arrow-left pl-3' title='previous' href='/users/search?user=".$searchedUser."&page=".($page - 1)."'></a>";
                }
                
                if($page < $pages) {
                    $pagination .="<a class='fa fa-arrow-right pl-3' title='next' href='/users/search?user=".$searchedUser."&page=".($page + 1)."'></a>
                                    <a class='fa fa-fast-forward pl-3' title='last' href='/users/search?user=".$searchedUser."&page=". $pages ."'></a>";
                }
                $pagination .= "</nav>";
            echo $pagination;
        }
    } else {
        echo "<span class='container'><h2>No matched found</h2></span>";
    }
?>
</div>