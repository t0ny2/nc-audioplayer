<?php
/**
 * ownCloud - Audio Player
 *
 * @author Marcel Scherello
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\audioplayer\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\IL10N;
use \OCP\IDb;

/**
 * Controller class for main page.
 */
class CategoryController extends Controller {
	
	private $userId;
	private $l10n;
	private $db;

	public function __construct(
			$appName, 
			IRequest $request, 
			$userId, 
			IL10N $l10n, 
			IDb $db 
			) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
		$this->db = $db;
	}

	/**
	 * @NoAdminRequired
	 * 
	 */
	public function getCategory(){
		$category=$this->params('category');
			
		$playlists= $this->getCategoryforUser($category);
	
		if(is_array($playlists)){
			$aPlayLists= array();
			foreach($playlists as $playinfo){
				$aPlayLists[]=['info' => $playinfo, 'songids' => $this->getSongIdsForCategory($category,$playinfo['id'])];
			}
		
			$result=[
				'status' => 'success',
				'data' => ['playlists' => $aPlayLists]
			];
		}else{
			$result=[
				'status' => 'success',
				'data' => 'nodata'
			];
		}
		$response = new JSONResponse();
		$response -> setData($result);
		return $response;
	}


	
	private function getCategoryforUser($category){
	
		if($category === 'Artist') {
			$SQL="SELECT  distinct(AT.`artist_id`) AS `id`, AA.`name` 
						FROM `*PREFIX*audioplayer_tracks` AT
						JOIN `*PREFIX*audioplayer_artists` AA
						on AA.`id` = AT.`artist_id`
			 			WHERE  AT.`user_id` = ?
			 			ORDER BY LOWER(AA.`name`) ASC
			 			";
		} elseif ($category === 'Genre') {
			$SQL="SELECT  `id`,`name` FROM `*PREFIX*audioplayer_genre`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			";
		} elseif ($category === 'Year') {
			$SQL="SELECT distinct(`year`) as `id` ,`year` as `name`  
						FROM `*PREFIX*audioplayer_tracks`
			 			WHERE  `user_id` = ?
			 			ORDER BY `id` ASC
			 			";
		} elseif ($category === 'All') {
			$SQL="SELECT distinct('0') as `id` ,'".(string)$this->l10n->t('All')."' as `name`  
						FROM `*PREFIX*audioplayer_tracks`
			 			WHERE  `user_id` = ?
			 			";
		} elseif ($category === 'Playlist') {
			$SQL="SELECT  `id`,`name` 
						FROM `*PREFIX*audioplayer_playlists`
			 			WHERE  `user_id` = ?
			 			ORDER BY LOWER(`name`) ASC
			 			";
		} elseif ($category === 'Folder') {
			$SQL="SELECT  distinct(FC.`fileid`) AS `id`,FC.`name` 
						FROM `*PREFIX*audioplayer_tracks` AT
						JOIN `*PREFIX*filecache` FC
						on FC.`fileid` = AT.`folder_id`
			 			WHERE  AT.`user_id` = ?
			 			ORDER BY LOWER(FC.`name`) ASC
			 			";
		}	
			
		$stmt =$this->db->prepareQuery($SQL);
		$result = $stmt->execute(array($this->userId));
		$aPlaylists=array();
		while( $row = $result->fetchRow()) {
			$aPlaylists[]=$row;
		}
		
		if(empty($aPlaylists)){
  			return false;
 		} else {
 			return  $aPlaylists;
		}
	}
	
	private function getSongIdsForCategory($category,$categoryId){

		if($category === 'Artist') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
			 		WHERE  `AT`.`artist_id` = ? 
			 		AND `AT`.`user_id` = ?
			 		ORDER BY `AT`.`title` ASC";
		} elseif ($category === 'Genre') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
					WHERE `AT`.`genre_id` = ?  
					AND `AT`.`user_id` = ?
					ORDER BY `AT`.`title` ASC";
		} elseif ($category === 'Year') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
					WHERE `AT`.`year` = ? 
					AND `AT`.`user_id` = ?
					ORDER BY `AT`.`title` ASC";
		} elseif ($category === 'All') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
					WHERE `AT`.`id` > ? 
					AND `AT`.`user_id` = ? 
					ORDER BY `AT`.`title` ASC";
		} elseif ($category === 'Playlist') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_playlist_tracks` `AP` 
					LEFT JOIN `*PREFIX*audioplayer_tracks` `AT` ON `AP`.`track_id` = `AT`.`id`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
			 		WHERE  `AP`.`playlist_id` = ?
			 		ORDER BY `AP`.`sortorder` ASC
			 		";
		} elseif ($category === 'Folder') {
			$SQL="SELECT  `AT`.`id` , `AT`.`title` ,`AT`.`number` ,`AT`.`length` ,`AA`.`name` AS `artist`, `AB`.`name` AS `album`,`AT`.`file_id`
					FROM `*PREFIX*audioplayer_tracks` `AT`
					LEFT JOIN `*PREFIX*audioplayer_artists` `AA` ON `AT`.`artist_id` = `AA`.`id`
					LEFT JOIN `*PREFIX*audioplayer_albums` `AB` ON `AT`.`album_id` = `AB`.`id`
					WHERE `AT`.`folder_id` = ? 
					AND `AT`.`user_id` = ?
					ORDER BY `AT`.`title` ASC";
		}

		$stmt = $this->db->prepareQuery($SQL);
		if ($category === 'Playlist') {
			$result = $stmt->execute(array($categoryId));
		} else {
				$result = $stmt->execute(array($categoryId, $this->userId));
		}
		$aTracks=[];
		while( $row = $result->fetchRow()) {
		
			try {
				$path = \OC\Files\Filesystem::getPath($row['file_id']);
			} catch (\Exception $e) {
				$file_not_found = true;
       		}
			$row['link'] = \OC::$server->getURLGenerator()->linkToRoute('audioplayer.music.getAudioStream').'?file='.rawurlencode($path);

			//$aTracks[]=$row['id'];
			$aTracks[]=$row;
		}
		return $aTracks;
	}
	
}
