<?php
namespace App;
use \Lib\Request,
    \Manager\Playlist,
    \Manager\User;

class Ajax extends \Lib\Base\App {

    public function init() {
    	define('AJAX', true);
        switch (Request::get('query')) {
            case 'search':
                echo $this->render('songs');
                die;
                break;
            case 'addPL':
                $playlistsManager = new Playlist;
                $status = $playlistsManager->addPL(
                    Request::get('name')
                );

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'delPL':
                $playlistsManager = new Playlist;
                $status = $playlistsManager->delPL(Request::get('id'));

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'editPL':
                $playlistsManager = new Playlist;
                $status = $playlistsManager->editPL(
                    Request::get('id'), 
                    Request::get('name')
                );

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'plStatus':
                $userManager = new User;
                $status = $userManager->updatePLSettings(
                    Request::get('id'), 
                    Request::get('status')
                );

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            case 'moveSongToPL':
                $playlistsManager = new Playlist;
                $status = $playlistsManager->moveSongToPL(
                    Request::get('fromId'), 
                    Request::get('toId'), 
                    Request::get('afterId'),
                    Request::get('songData')
                );

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'delSongFromPL':
                $playlistsManager = new Playlist;
                $status = $playlistsManager->delSongFromPL(
                    Request::get('id'), Request::get('plId')
                );

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'reloadPL':
                echo $this->render('playlists');
                die;
                break;

            case 'login':
                $request = Request::get('user');
                parse_str($request, $request);
                
                $usermanager = new User;
                $user = $usermanager->login(
                    $request['login'], $request['password']
                );
                
                echo $this->render('user');
                die;
                break;

            case 'logout':
                $usermanager = new User;
                $usermanager->logout();

                echo $this->render('user');
                die;
                break;
            
            case 'deleteSong':
            	if (\Lib\Config::getInstance()->getOption('storage', 'delete_by_request') == 'yes') {
	                $path = 'assets/' . Request::get('id') . '.mp3';
	                if (file_exists($path)) {
	                    unlink($path);
	                }
            	}
                die;
                break;

            case 'getSong':
                # stat
                if ( Request::get('artist') ) {
                    $statManager = new \Manager\Stat;
                    $statManager->log(
                        Request::get('artist')
                    );
                }
                # /stat
				
                $id = preg_replace('#[^a-f0-9]#', '', Request::get('id'));
                
                $path = $id . '.mp3';
				
                $storage = \Lib\Storage::getInstance(); 
                $songs_manager = new \Manager\Songs;
                
                if ( !$storage->exists($path) ) {
                    $url = Request::get('url');
                    if (!preg_match('#http://cs[0-9]+\.vkontakte\.ru/u[0-9]+/audio/[a-f0-9]+\.mp3#', $url)) {
                    	die;
                    }
                    $headers = \Lib\Curl::get_headers($url, true);
                    $status = substr($headers[0], 9, 3);

                    if ('404' == $status) {
                        $song = reset(\Lib\AudioParser::search(
                            Request::get('artist') . ' - ' . Request::get('name')
                        ));

                        $url = $song['url'];
                        $playlistsManager = new Playlist;
                        $playlistsManager->updateSongInfo(
                            Request::get('id'), 
                            array(
                                'url' => $url
                            )
                        );
                    }
                    $song = file_get_contents($url);
                    $storage->save($song, $path);
                    $songs_manager->updateSong($id, array('filename' => $path, 'size' => strlen($song)));
                }
				
                # stat
				if (!isset($statManager)) {
					$statManager = new \Manager\Stat; 
				}
				$statManager->logSong($id);
				# /stat
				
                echo json_encode(array(
                    'url' => "./assets/{$path}"
                ));
                die;
                break;

            default:
                break;
        }
    }

}
