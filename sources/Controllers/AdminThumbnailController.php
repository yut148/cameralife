<?php
namespace CameraLife\Controllers;

use CameraLife\Views as Views;
use CameraLife\Models as Models;

/**
 * Displays the Admin Appearance page
 * @author William Entriken <cameralife@phor.net>
 * @copyright 2014 William Entriken
 * @access public
 */

class AdminThumbnailController extends HtmlController
{
    public function __construct()
    {
        parent::__construct();
        $this->title = 'Admin Thumbnails';
        $this->icon = 'list';
    }

    public function handleGet($get, $post, $files, $cookies)
    {
        if (Models\User::currentUser($cookies)->authorizationLevel < 5) {
            throw new \Exception('You are not authorized to view this page');
        }

        ini_set('max_execution_time', 9000);
        chdir(constant('BASE_DIR'));
        $lastdone = isset($get['lastdone']) ? (int)$get['lastdone'] : 0;
        $starttime = isset($get['starttime']) ? (int)$get['starttime'] : time();
        $numdone = isset($get['numdone']) ? (int)$get['numdone'] : 0;
        $phpself = self::getUrl();
        
        /* Rescan */
        if (!isset($get['lastdone'])) {
            Models\Folder::update();
        }

        /* Set up the page view */
        $this->htmlHeader($cookies);


        //TODO BREAKING MVC HERE BECAUSE OF INTREMENTAL RENDERING
        echo '<h2>Rendering thumbnails <small>To avoid a delay when viewing photos for the first time</small></h2>';

        $total = Models\Database::selectOne('photos', 'count(*)');
        $done = Models\Database::selectOne('photos', 'count(*)', "id <= $lastdone");
        $todo = Models\Database::selectOne('photos', 'count(*)', "id > $lastdone");
        $timeleft = ceil((time() - $starttime) * $todo / ($numdone + $done / 1000 + 1) / 60);

        echo "<p>Progress: " . number_format($done) . ' of ' . number_format($total) . " done";
        echo " (about $timeleft minutes left)";
        echo "</p>\n";
        $percentage = ($done / $total * 100);
        echo "<progress class=\"progress\" value=\"$percentage\" max=\"100\">{$percentage}%</progress>";

        $next1000 = Models\Database::select('photos', 'id', "id > $lastdone AND status != 9", 'ORDER BY id LIMIT 500');
        $fixed = 0;
        flush();
        while (($next = $next1000->fetchAssoc()) && ($fixed < 10)) {
            $photo = Models\Photo::getPhotoWithID($next['id']);
            $redo = $photo->isCacheMissing();
            if ($redo) {
                echo "<div>Updating #" . $next['id'] . "</div>\n";
                $photo->generateThumbnail();
                echo "<div>Updated #" . $next['id'] . "</div>\n";
                flush();
                $fixed++;
                $photo->destroy();
            }
            $lastdone = $next['id'];
        }

        $numdone += $fixed;
        if ($todo > 0) {
            echo "<script language='javascript'>window.setTimeout('window.location=\"" . htmlspecialchars($phpself) . "?lastdone=$lastdone&starttime=$starttime&numdone=$numdone\"',400)</script>\n";
            echo "<p><a href=\"?lastdone=$lastdone&starttime=$starttime&numdone=$numdone\">Click here to continue</a> if the Javascript redirect doesn't work.</p>\n";
        }

        $this->htmlFooter();
    }
}
