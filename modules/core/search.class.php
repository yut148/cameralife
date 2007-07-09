<?php
  # the class for getting and using search
  
class Search
{
  var $mySearchPhotoCondition;
  var $mySearchAlbumCondition;
  var $mySearchFolderCondition;
  var $myLimit;
  var $mySort;
  var $mySql;
  var $myQuery;

  function Search($query)
  {
    global $cameralife, $_POST, $_GET;

    if (!get_magic_quotes_gpc())
      addslashes($this->myQuery=$query);
    else
      $this->myQuery=$query;
    $this->myExtra='';
    $special = array('?', '.');
    $special_escaped = array('[?]', '[.]');
    foreach(explode(' ', $query) as $term)
    {
      $term = addslashes($term);
      $term = str_replace($special, $special_escaped, $term);
      $searchPhotoConditions[] = "concat(description,' ',keywords) REGEXP '(^|[[:blank:]])".addslashes(preg_quote(stripslashes($term)))."'";
      $searchAlbumConditions[] = "name LIKE '%$term%'";
      $searchFolderConditions[] = "path LIKE '%$term%'";
    }
    $this->mySearchPhotoCondition = implode(' AND ', $searchPhotoConditions);
    $this->mySearchAlbumCondition = implode(' AND ', $searchAlbumConditions);
    $this->mySearchFolderCondition = implode(' AND ', $searchFolderConditions);
    
    if ($this->mySort = $_POST['sort'])
      setcookie("sort",$this->mySort);
    elseif 
      ($this->mySort = $_GET['sort']);
    elseif 
      ($this->mySort = $_COOKIE['sort']);
    else
      $this->mySort = 'newest';

    $start = $_GET['start']
      or $start = 0;
    $this->myLimit = "LIMIT $start, 12";
  }

  function SetSort($sort)
  {
    $this->mySort = $sort;
  }

  function SortOptions()
  {
    $retval = array();
    $retval[] = array('newest', 'Newest First');
    $retval[] = array('oldest', 'Oldest First');
    $retval[] = array('az', 'Alphabetically (A-Z)');
    $retval[] = array('za', 'Alphabetically (Z-A)');
    $retval[] = array('popular', 'Popular First');
    $retval[] = array('unpopular', 'Unpopular First');
    $retval[] = array('rand', 'Random');
    if (is_object($this) && array_key_exists($this->mySort, $retval))
      $retval['$this->mySort'][] = 'selected';
    return $retval;
  }

  function GetCounts()
  {
    global $cameralife;

    $selection = "COUNT(DISTINCT id)";
    $photos = $cameralife->Database->SelectOne('photos', 'COUNT(*)', $this->mySearchPhotoCondition.' AND status=0');
    $albums = $cameralife->Database->SelectOne('albums', 'COUNT(*)', $this->mySearchAlbumCondition);
    $folders = $cameralife->Database->SelectOne('photos', 'COUNT(DISTINCT path)', $this->mySearchFolderCondition.' AND status=0');

    return array('photos'=>$photos, 'albums'=>$albums, 'folders'=>$folders);
  }  

  function SetPage($start, $pagesize=12)
  {
//    $this->myLimit = 'LIMIT '.($page*$pagesize).','.$pagesize;
    $this->myLimit = 'LIMIT '.$start.','.$pagesize;
  }

  function GetPhotos()
  {
    global $cameralife;

    switch ($this->mySort)
    {
      case 'newest':    $sort = 'id desc'; break;
      case 'oldest':    $sort = 'id'; break;
      case 'az':        $sort = 'description'; break;
      case 'za':        $sort = 'description desc'; break;
      case 'popular':   $sort = 'hits desc'; break;
      case 'unpopular': $sort = 'hits'; break;
      case 'rand':      $sort = 'rand()'; break;
      default:          $sort = 'id desc';
    }

    $condition = $this->mySearchPhotoCondition.' AND status=0';
    $query = $cameralife->Database->Select('photos', 'id', $condition, 'ORDER BY '.$sort.' '.$this->myLimit);
    $photos = array();
    while ($row = $query->FetchAssoc())
      $photos[] = new Photo($row['id']);

    return $photos;
  }

  function GetAlbums()
  {
    global $cameralife;

    switch ($this->mySort)
    {
      case 'newest':    $sort = 'albums.id desc'; break;
      case 'oldest':    $sort = 'albums.id'; break;
      case 'az':        $sort = 'description'; break;
      case 'za':        $sort = 'description desc'; break;
      case 'popular':   $sort = 'hits desc'; break;
      case 'unpopular': $sort = 'hits'; break;
      case 'rand':      $sort = 'rand()'; break;
      default:          $sort = 'albums.id desc';
    }

    $condition = $this->mySearchAlbumCondition.' AND status=0 AND albums.poster_id=photos.id';
    $query = $cameralife->Database->Select('photos, albums', 'albums.id', $condition, 'ORDER BY '.$sort.' '.$this->myLimit);

    $albums = array();
    while ($row = $query->FetchAssoc())
      $albums[] = new Album($row['id']);

    return $albums;
  }

  function GetFolders()
  {
    global $cameralife;

    switch ($this->mySort)
    {
      case 'newest':    $sort = 'id desc'; break;
      case 'oldest':    $sort = 'id'; break;
      case 'az':        $sort = 'description'; break;
      case 'za':        $sort = 'description desc'; break;
      case 'popular':   $sort = 'hits desc'; break;
      case 'unpopular': $sort = 'hits'; break;
      case 'rand':      $sort = 'rand()'; break;
      default:          $sort = 'id desc';
    }

    $condition = $this->mySearchFolderCondition.' AND status=0';
    $query = $cameralife->Database->Select('photos', 'path', $condition, 'GROUP BY path ORDER BY '.$sort.' '.$this->myLimit);
    $folders = array();
    while ($row = $query->FetchAssoc())
      $folders[] = new Folder($row['path']);
    return $folders;
  }

  function GetSmallIcon()
  {
    return array('name'=>'Search for '.$this->myQuery,
                 'href'=>"search.php&#63;q=".urlencode($this->myQuery),
                 'image'=>'small-search');
  }

}

?>