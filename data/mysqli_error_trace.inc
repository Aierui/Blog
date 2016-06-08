<?php  exit();
/*
Page: /cloud/index_body.php?dopost=getRightSide
Error: Table 'blog.member' doesn't exist <br />Error sql: <font color='red'>SELECT COUNT(*) AS dd FROM `member` LIMIT 0,1;</font>
Time2016-05-18 18:47:56
*/
?>
<?php  exit();
/*
Page: /cloud/media_main.php
Error: Table 'blog.member' doesn't exist <br />Error sql: <font color='red'>SELECT COUNT(*) AS dd FROM uploads u
LEFT JOIN admin a ON  a.id = u.mid
LEFT JOIN member m ON m.mid = u.mid
 WHERE (u.title LIKE '%%' OR u.url LIKE '%%') LIMIT 0,1;</font>
Time2016-05-18 18:48:32
*/
?>
<?php  exit();
/*
Page: /cloud/media_main.php
Error: Table 'blog.member' doesn't exist <br />Error sql: <font color='red'>SELECT u.aid,u.title,u.url,u.mediatype,u.filesize,u.mid,u.uptime
,a.userid AS adminname,m.userid AS membername
FROM uploads u
LEFT JOIN admin a ON  a.id = u.mid
LEFT JOIN member m ON m.mid = u.mid
 WHERE (u.title LIKE '%%' OR u.url LIKE '%%')  ORDER BY u.aid DESC LIMIT 0,20</font>
Time2016-05-18 18:48:32
*/
?>
<?php  exit();
/*
Page: /cloud/index_body.php?dopost=getRightSide
Error: Table 'blog.member' doesn't exist <br />Error sql: <font color='red'>SELECT COUNT(*) AS dd FROM `member` LIMIT 0,1;</font>
Time2016-05-18 19:04:20
*/
?>
