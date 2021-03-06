<?php
namespace CameraLife\Models;

/**
 * Returns photos, tags, and folders as restricted by QUERY and paging options
 * @author William Entriken <cameralife@phor.net>
 * @access public
 * @copyright 2001-2014 William Entriken
 */
class Favorites extends Search
{
    ///TODO: delete from ratings table where rating = 0 and simplify some queries here

    private $whereRestriction = '';

    public static function favoritesForCurrentUser($cookies)
    {
        $currentUser = User::currentUser($cookies);
        $retval = new Favorites;

        $retval->whereRestriction = 'ratings.user_ip = "' . $currentUser->remoteAddr . '"';
        if ($currentUser->isLoggedIn) {
            ///TODO security: need to bind user name
            $retval->whereRestriction = 'ratings.username = "' . $currentUser->name . '"';
        }
        return $retval;
    }

    /**
     * Returns photos per QUERY, privacy, and paging restrictions
     *
     * @access public
     * @return Photo[]
     */
    public function getPhotos()
    {
        $sort = $this->photoSortSqlForOption($this->sort);
        $condition = $this->whereRestriction;
        if (!$this->showPrivatePhotos) {
            $condition .= ' AND status = 0';
        }
        $query = Database::Select(
            'ratings',
            'photos.id',
            $condition,
            'ORDER BY ' . $sort . ' ' . 'LIMIT ' . $this->offset . ',' . $this->pageSize,
            'LEFT JOIN photos ON ratings.id = photos.id
             LEFT JOIN exif ON photos.id=exif.photoid and exif.tag="Date taken"'
        );
        $photos = array();
        while ($row = $query->fetchAssoc()) {
            $photos[] = Photo::getPhotoWithID($row['id']);
        }

        return $photos;
    }

    /**
     * Counts photos per QUERY, and privacy restrictions
     *
     * @access public
     * @return int
     */
    public function getPhotoCount()
    {
        $condition = $this->whereRestriction;
        if (!$this->showPrivatePhotos) {
            $condition .= 'AND status = 0';
        }

        return Database::selectOne(
            'ratings',
            'COUNT(*)',
            $condition,
            null,
            'LEFT JOIN photos ON ratings.id = photos.id',
            null
        );
    }
}
