<?php 
include("includes/header.php");

$message_obj = new Message($con, $userLoggedIn);

if(isset($_GET['profile_username'])) {
	$username = $_GET['profile_username'];
	$user_details_query = mysqli_query($con, "SELECT * FROM users WHERE username='$username'");
	$user_array = mysqli_fetch_array($user_details_query);

	$num_friends = (substr_count($user_array['friend_array'], ",")) - 1;
}



if(isset($_POST['remove_friend'])) {
	$user = new User($con, $userLoggedIn);
	$user->removeFriend($username);
}

if(isset($_POST['add_friend'])) {
	$user = new User($con, $userLoggedIn);
	$user->sendRequest($username);
}
if(isset($_POST['respond_request'])) {
	header("Location: requests.php");
}

if(isset($_POST['post_message'])) {
  if(isset($_POST['message_body'])) {
    $body = mysqli_real_escape_string($con, $_POST['message_body']);
    $date = date("Y-m-d H:i:s");
    $message_obj->sendMessage($username, $body, $date);
  }

  $link = '#profileTabs a[href="#messages_div"]';
  echo "<script> 
          $(function() {
              $('" . $link ."').tab('show');
          });
        </script>";


}

$profle_of_user = new User($con, $user_array['username']);
$checkIsFriend = 'True';
if ($profle_of_user->isFriend($userLoggedIn)) {
  $checkIsFriend = 'True';
} else {
  $checkIsFriend = 'False';
}

?>

 	<style type="text/css">
	 	.wrapper {
	 		margin-left: 0px;
			padding-left: 0px;
	 	}

 	</style>
	
 	<div class="profile_left">
 		<img src="<?php echo $user_array['profile_pic']; ?>">

 		<div class="profile_info">
      <p><?php echo "Name: " . $user_array['first_name']." ". $user_array['last_name'] ; ?></p>
 			<p><?php echo "Posts: " . $user_array['num_posts']; ?></p>
 			<p><?php echo "Likes: " . $user_array['num_likes']; ?></p>
 			<p><?php echo "Friends: " . $num_friends ?></p>
      <p>
      <?php
              if ($user_array['dogowner'] == 'true'){
                echo "<strong>Dog owner</strong>";
              } else {
                echo "<strong>Dog walker</strong>";
              }
      ?>
      </p>
 		</div>

 		<form action="<?php echo $username; ?>" method="POST">
 			<?php 
 			$profile_user_obj = new User($con, $username); 
 			if($profile_user_obj->isClosed()) {
 				header("Location: user_closed.php");
 			}

 			$logged_in_user_obj = new User($con, $userLoggedIn); 

 			if($userLoggedIn != $username) {

 				if ($logged_in_user_obj->isFriend($username) && $logged_in_user_obj->isDogOwner() == 'true') {
 					echo '<input type="submit" name="remove_friend" class="danger" value="End dog walker"><br>';
 				}
 				else if ($logged_in_user_obj->didReceiveRequest($username)) {
 					echo '<input type="submit" name="respond_request" class="warning" value="Respond to Request"><br>';
 				}
 				else if ($logged_in_user_obj->didSendRequest($username)) {
 					echo '<input type="submit" name="" class="default" value="Request Sent"><br>';
 				}
 				else {
          if ($logged_in_user_obj->isDogOwner() == 'true' && !$logged_in_user_obj->isFriend($username)) {
 					echo '<input type="submit" name="add_friend" class="success" value="start dog walker"><br>';
          } else if(!$logged_in_user_obj->isFriend($username)) {
          echo '<input type="submit" name="add_friend" class="success" value="request dog walking"><br>';           
          }
        }
      }
 			?>
 		</form>
 		<input type="submit" class="deep_blue" data-toggle="modal" data-target="#post_form" value="Post Something">
 	</div>


	<div class="profile_main_column column">

    <ul class="nav nav-tabs" role="tablist" id="profileTabs">
      <li role="presentation" class="active"><a href="#newsfeed_div" aria-controls="newsfeed_div" role="tab" data-toggle="tab">Newsfeed</a></li>
      <li role="presentation"><a href="#messages_div" aria-controls="messages_div" role="tab" data-toggle="tab">Messages</a></li>
       <li role="presentation"><a href="#trace_user_map" aria-controls="trace_user_map" role="tab" data-toggle="tab">Trace users</a></li>
    </ul>

    <div class="tab-content">

      <div role="tabpanel" class="tab-pane active" id="newsfeed_div">
        <div class="posts_area"></div>
        <img id="loading" src="assets/images/icons/loading.gif">
      </div>


      <div role="tabpanel" class="tab-pane" id="messages_div">
        <?php  
        

          echo "<h4>You and <a href='" . $username ."'>" . $profile_user_obj->getFirstAndLastName() . "</a></h4><hr><br>";

          echo "<div class='loaded_messages' id='scroll_messages'>";
            echo $message_obj->getMessages($username);
          echo "</div>";
        ?>



        <div class="message_post">
          <form action="" method="POST">
              <textarea name='message_body' id='message_textarea' placeholder='Write your message ...'></textarea>
              <input type='submit' name='post_message' class='info' id='message_submit' value='Send'>
          </form>

        </div>

        <script>
          $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
              var div = document.getElementById("scroll_messages");
              div.scrollTop = div.scrollHeight;
          });
        </script>
      </div>


      <div class="tab-pane" id="trace_user_map">
        <button onclick ="SetMarker()">Trace current location</button>
        <div id='map_of_two_user'></div>
      </div>


    </div>


	</div>

<!-- Modal -->
<div class="modal fade" id="post_form" tabindex="-1" role="dialog" aria-labelledby="postModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="postModalLabel">Post something!</h4>
      </div>

      <div class="modal-body">
      	<p>This will appear on the user's profile page and also their newsfeed for your friends to see!</p>

      	<form class="profile_post" action="" method="POST">
      		<div class="form-group">
      			<textarea class="form-control" name="post_body"></textarea>
      			<input type="hidden" name="user_from" value="<?php echo $userLoggedIn; ?>">
      			<input type="hidden" name="user_to" value="<?php echo $username; ?>">
      		</div>
      	</form>
      </div>


      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" name="post_button" id="submit_profile_post">Post</button>
      </div>
    </div>
  </div>
</div>


<script>
  var userLoggedIn = '<?php echo $userLoggedIn; ?>';
  var profileUsername = '<?php echo $username; ?>';

  $(document).ready(function() {

    $('#loading').show();

    //Original ajax request for loading first posts 
    $.ajax({
      url: "includes/handlers/ajax_load_profile_posts.php",
      type: "POST",
      data: "page=1&userLoggedIn=" + userLoggedIn + "&profileUsername=" + profileUsername,
      cache:false,

      success: function(data) {
        $('#loading').hide();
        $('.posts_area').html(data);
      }
    });

    $(window).scroll(function() {
      var height = $('.posts_area').height(); //Div containing posts
      var scroll_top = $(this).scrollTop();
      var page = $('.posts_area').find('.nextPage').val();
      var noMorePosts = $('.posts_area').find('.noMorePosts').val();

      if ((document.body.scrollHeight == document.body.scrollTop + window.innerHeight) && noMorePosts == 'false') {
        $('#loading').show();

        var ajaxReq = $.ajax({
          url: "includes/handlers/ajax_load_profile_posts.php",
          type: "POST",
          data: "page=" + page + "&userLoggedIn=" + userLoggedIn + "&profileUsername=" + profileUsername,
          cache:false,

          success: function(response) {
            $('.posts_area').find('.nextPage').remove(); //Removes current .nextpage 
            $('.posts_area').find('.noMorePosts').remove(); //Removes current .nextpage 
            $('.posts_area').find('.noMorePostsText').remove(); //Removes current .nextpage 

            $('#loading').hide();
            $('.posts_area').append(response);
              
          }
        });

      } //End if 

      return false;

    }); //End (window).scroll(function())


  });

  </script>
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=AIzaSyCeqEU7yQR9ohoje4aSvTCMbvFklMfzFPg&sensor=false"></script>
<script type="text/javascript">
    var map;
    var marker;
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
                var arr = this.responseText.split(' ');
                var initlat = Number(arr[0]);
                var initlng = Number(arr[1]);
                LoadMap(initlat,initlng)
            }
    }
    xmlhttp.open("GET", "trace_users.php?username=" + "<?php echo $user_array['username']?>", true);
    xmlhttp.send();
    function LoadMap(lat,lng) {
        var mapOptions = {
            center: new google.maps.LatLng(lat, lng),
            zoom: 15,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        map = new google.maps.Map(document.getElementById("map_of_two_user"), mapOptions);
        //SetMarker();
    };
    LoadMap();
    function SetMarker() {
        if (marker != null) {
            marker.setMap(null);
        }
        if('<?php echo $checkIsFriend === 'True' ?>') {
        var xmlhttp = new XMLHttpRequest();
          xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    var arr = this.responseText.split(' ');
                    var myLatlng = new google.maps.LatLng(Number(arr[0]), Number(arr[1]));
                    marker = new google.maps.Marker({
                        position: myLatlng,
                        map: map
                    });
                }
            }
            xmlhttp.open("GET", "trace_users.php?username=" + "<?php echo $user_array['username']?>", true);
            xmlhttp.send();
          } else {
            alert("Have not start dog walking, cannot trace other's location");
          }
    };
</script>
	</div>
</body>
</html>