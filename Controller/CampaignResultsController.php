<?php
App::uses('CampaignsAppController', 'Campaigns.Controller');
/**
 * CampaignResults Controller
 *
 * @property CampaignResults $CampaignResults
 */
class CampaignResultsController extends CampaignsAppController {	
/**
 * Uses
 *
 * @var array
 */
	public $uses = 'Campaigns.CampaignResult';
	
/**
 * Components
 *
 * @var array
 */
 var $components = array(
 	'Auth' => array(
 		'loginRedirect' => array(
 			'controller' => 'campaign_results',
 			'action' => 'claim'
			)
		), 
	'Facebook.Connect'
	);

 public $allowedActions = array(
 	'result',
 	'claim',
 	'vouchers',
 	'redemption',
 	'redemption_swipe',
 	'redemption_swipe_confirm',
 	'update',
 	'home'
	);
 	 
/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->set('campaignResults', $campaignResults = $this->paginate());
	}
	//9459322447
	public function edit($campaign_id, $result)	{
		$user_id = $this->Session->read('Auth.User.id');
		$response=array();	
		$campaign_result = $this->CampaignResult->find('first', array('conditions'=>array('creator_id'=>$user_id, 'campaign_id'=>$campaign_id, 'status'=>STATUS_PENDING, 'parent_id IS NULL')));
		if(!$campaign_result)	{
			$data['CampaignResult'] = array('creator_id'=>$user_id, 'campaign_id'=>$campaign_id, 'coupon_value'=>$result);
			$this->CampaignResult->save($data);
			$response['status']='saved';
			$response['campaign_result_id']=$this->CampaignResult->id;
		}	else	{
			$response['status']='exists';
			$response['campaign_result_id']=$campaign_result['CampaignResult']['id'];
		}
		header("Content-type: application/json");
		echo json_encode($response);
		exit;
	}
	
	/**
 * index method
 * This method is used to show the share page after user has spinned the wheel, may be there need to add a page between the spin page and results/share page
 * @return void
 */
	
	public function result($id=null)	{
		$upload_dir = ROOT.DS.SITE_DIR.DS.'/Locale/View/webroot/tmp';
		
		App::import('Lib', 'Facebook.FB');		
		$FB = new FB();
		
		//debug($fbfriends);
		//$ret_obj = $FB->api('/me/outbox');
		
		//$fbfriends = $FB->api("/me/friends");		
		
		//$friendList = json_decode($ret_obj);
		
		if($this->request->is('post'))	{
			
			debug($this->request->data);
			
			$fb_tos = $this->request->data['fbfriend'];
			
			//exit;
			
			
			$id = $this->request->data['CampaignResult']['id'];
			$feed = array(
					'link' => Router::url(array('controller'=>'campaign_results', 'action'=>'claim', base64_encode($id)), true),
					'message' => 'This is how it is coming Richard'
			 );
			if($this->request->data['CampaignResult']['imagefile']['error']==0)	{
				//pr("uploading");
				if(move_uploaded_file($this->request->data['CampaignResult']['imagefile']['tmp_name'], $upload_dir . DS . $this->request->data['CampaignResult']['imagefile']['name']))	{
					$picture = Router::url('/tmp/' . $this->request->data['CampaignResult']['imagefile']['name'], true);
				}
			}
			if($picture)	{
				$feed['picture'] = $picture;
			}
			
			foreach($fb_tos as $friend_id)	{
					$ret_obj = $FB->api('/'.$friend_id.'/feed', 'POST', $feed);
				  
				 debug($ret_obj);
		  }
		  
		  exit;
			//pr($feed);
		}
		
		if($this->Session->read('Facebook.Friends'))	{
			$fbfriends = $this->Session->read('Facebook.Friends');
		}	else	{
			$fbfriends = $FB->api("/me/friends");
			$this->Session->write('Facebook.Friends', $fbfriends);
		}
		

		
		//$FB = FB::api('/me');
		//$FBME = $FB->api('/me');
		
		//debug($FBME);
		//$FacebookApi = new FB();
		//$FBME = $FacebookApi->api('/me/photos');
		//debug($FB);
		
		/*graph.facebook.com
  /{user-id}/feed?
    message={message}&
    access_token={access-token}*/
		
    //$ret_obj = $FB->api('/me/friendlists');
    
		/*
		$ret_obj = $FB->api('/100004388857579/feed', 'POST',
				array(
					'link' => 'http://sharendipity.buildrr.com/',
					'message' => 'Posting with the PHP SDK!'
		 ));*/
		 
		//$ret_obj = $FB->api("/me/friends");
		
		//$friendList = json_decode($ret_obj);
		
		//debug($ret_obj);
		 
		//debug($ret_obj);
     //echo '<pre>Post ID: ' . $ret_obj['id'] . '</pre>';
		
		//debug($this->CampaignResult->query('SHOW COLUMNS FROM campaign_results'));
		//debug($this->CampaignResult->query("delete from campaign_results where id='52fcd653-a7c8-43b9-befd-21460ad25527'"));
		
		//$data['CampaignResult'] = array('user_id'=>1, 'campaign_id'=>'52fb5844-06b4-4a6f-815b-49fd0ad25527', 'result'=>'10');
		//$this->CampaignResult->save($data);
		//debug($this->Connect->user());
		if($this->Connect->user())	{ //facebook check user
			$this->FB = $this->Connect->user();
			//debug($this->FB);
			$facebook_id = $this->FB['id'];
			//debug($this->facebookUser);
		}
		
		//echo $facebook_id;
		
		$user_id = $this->Session->read('Auth.User.id');
		$this->CampaignResult->contain(array('Campaign', 'Creator'));
		//$campaign_result = $this->CampaignResult->find('first', array('conditions'=>array('CampaignResult.creator_id'=>$user_id, 'CampaignResult.campaign_id'=>$campaign_id)));
		
		$campaign_result = $this->CampaignResult->find('first',  array('conditions'=>array('CampaignResult.creator_id'=>$user_id, 'CampaignResult.id'=>$id)));
		//debug($campaign_result);
		$campaign_id = $campaign_result['CampaignResult']['campaign_id'];
		
		//get a list of fb user with whom this user has shared a coupon for this campaign already and filter those fb id fs out from possible recipients
		$fbsharedwith = $this->CampaignResult->find('all', array('conditions'=>array('CampaignResult.campaign_id'=>$campaign_id, 'sender_id'=>$user_id, 'CampaignResult.recepient_fbid IS NOT NULL'), 'fields'=>array('CampaignResult.recepient_fbid')));
		if(isset($fbfriends['data']) && count($fbfriends['data'])>0)	{
			asort($fbfriends['data']);
			foreach($fbfriends['data'] as $i=>$data)	{
				if($fbsharedwith) 	{
					foreach($fbsharedwith as $fbsentto)	{
						if($data['id']==$fbsentto['CampaignResult']['recepient_fbid'])	{
							unset($fbfriends['data'][$i]);
						}
					}
				}
			}
		}
		$this->set(compact('campaign_result', 'facebook_id', 'fbfriends'));
		
	}
	
/**
 * claim method
 * This method is supposed to be clicked by a facebook user. This link is sent to user's fb message box as a gift coupon and user click on this link to claim their reward. 
 
 Completing the redemption by user a gift coupon and reward points or both are awarded to the sender. This link can be clicked by multiple users so this need to be coded in a manner so that one user can redeem their coupon only once. Probaly need to track the fb user id when creating new account for them.
 
 Also this method is called by the facebook API Open Graph Object url parse. So need to differentiate the call. Using HTTP_USER_AGENT to identify the call from FB
 * @return void
 */
	
	function claim($resultId ='')	{ //	
		
		//debug($this->Auth->loginRedirect);
		//exit;
		//print_r($_SERVER);
		
						//debug($this->CampaignResult->find('all'));
					//exit;
		
		//$agent_facebook = false;
		
		if(strstr($_SERVER['HTTP_USER_AGENT'], 'facebookexternalhit'))	{
			$agent_facebook = true;
		}
		//print_r($_REQUEST);		
		//exit;		
		//debug($this->CampaignResult->query('delete from campaign_results where 1=1'));
		
		//$r = $this->CampaignResult->query("select * from campaign_invites");
		//$this->CampaignResult->query("drop table zbk_campaign_results");
		//$this->CampaignResult->query("drop table zbk_campaigns");		
		//$campaign_result_id='530c3cf1-48e0-489c-a502-73240ad25527';
		$campaign_result_id= base64_decode($resultId); //this id was encoded to avoid the error in FB dialog API
		// checking to see if base64 decode actually produced anything, if not, then we have a real id.
		$campaign_result_id = !empty($campaign_result_id) ? $campaign_result_id : $resultId; //Richard undid, because the link received on Facebook wasn't encoded 3/18/2014
		if (empty($campaign_result_id)) {
			// we seem to get here, if you share a link with someone who is not already registered on sharendipity
			// and they are signing up for the first time, coming from a claim link, it takes them to login, then when
			// they get back to this claim page the actual id is given, not a base64_encoded() id. 
			// IMPORTANT : I fixed it by putting the line above in (190)
			debug('base 64 decode did not work');
			debug($resultId);
			exit;
		}
		
		$this->CampaignResult->contain(array('Campaign', 'Creator'));
		$this->CampaignResult->id = $campaign_result_id;
		$campaign_result = $this->CampaignResult->read();
		
		if(!$campaign_result)	{
			$this->Session->setFlash(__('No campaign found! You got here from a bad link.')); 
			$this->redirect('/users/users/my');
			//die('No such campaign');
		}
		
		$sender_id = $campaign_result['CampaignResult']['creator_id']; //it is creator_id who shared it. We are updating the compaign result after user has save using ajax(Todo) so it's better we pick creator_id who is the sender in most cases.
		
		//debug($campaign_result);
		//$this->Session->write('Campaign.campaign_claim_id', $campaign_result_id);
		$fbmetas = 'true';
		
		if(!$agent_facebook)	{
			//$this->Session->write('Campaign.campaign_claim_id', base64_encode($campaign_result_id));
			if($campaign_result)	{
				if(!$this->Auth->loggedIn())	{
					$this->Session->write('Campaign.campaign_claim_id', base64_encode($campaign_result_id));
					$this->Auth->loginRedirect = array('controller' => 'campaign_results', 'action' => 'claim', $resultId);
					$this->redirect(array('controller'=>'users', 'plugin'=>'users', 'action'=>'login'));
				}
				$user_id = $this->Session->read('Auth.User.id');
						
				if($sender_id==$user_id)	{
					die('Cannot claim your own gift coupon');
				}
				
				if ($this->Connect->user())	{ //facebook check user
					$this->FB = $this->Connect->user();
					$facebook_id = $this->FB['id'];
				}
				if (empty($facebook_id)) {
					// we seem to get here, if you share a link with someone who is not already registered on sharendipity
					// and they are signing up for the first time, coming from a claim link
					debug('No facebook id');
					debug($this->FB);
					exit;
				}
				//$this->loadModel('Campaigns.CampaignInvite');
				
				//debug($this->CampaignResult->find('all'));
				//	exit;
				
				//debug($this->CampaignInvite->query('delete from campaign_invites where 1=1'));
				
				if($this->Session->read('Campaign.campaign_claim_id'))	{
					$data = array('parent_id'=>$campaign_result_id, 'campaign_id'=>$campaign_result['CampaignResult']['campaign_id'], 'sender_id'=>$campaign_result['CampaignResult']['creator_id'], 'recepient_id'=>$user_id, 'status'=>STATUS_USABLE, 'recepient_fbid'=>$facebook_id, 'coupon_value'=>$campaign_result['CampaignResult']['coupon_value']); //status 1 means this coupon is ready to use.
					
					if($facebook_id)	{					
						$conditions['recepient_fbid'] = $facebook_id; //it's better to find with fb it and there is chance that user may try to click and receive a coupon multiple times.
					}	else	{
						$conditions['recepient_id'] = $user_id;
					}
					$conditions['parent_id'] = $campaign_result_id;
					
					$first = $this->CampaignResult->find('first', array('conditions'=>$conditions));
				
					if(!$first)	{
						//debug($data);
						$this->CampaignResult->create();
						$saved = $this->CampaignResult->save($data);
						/*if($facebook_id)	{
							App::import('Lib', 'Facebook.FB');		
							$FB = new FB();
							$ret_obj = $FB->api('/me/feed', 'POST',
								array(
											'link' => 'http://sharendipity.buildrr.com/',
											'message' => 'I redeemed a gift coupon worth $'.$campaign_result['CampaignResult']['result'].'!',
											'picture' => Router::url('/img/big-shoulders.jpg', true)
								 ));							
							//debug($ret_obj);
						}*/
						$this->Session->delete('Campaign.campaign_claim_id');
						$redeemed_thankyou = true;
					}	else	{
						$redeemed_thankyou = true;
						$already_redeemed = true;
					}
				}
			}	
		}
		
			//$this->Campaign->
			
			//$this->set('campaign', $campaign = $this->Campaign->read());
			$campaign = $campaign_result;
			//debug($campaign_result);
			$meta_description = $campaign_result['Campaign']['description'];		
			//$this->page_title = $campaign_result['Campaign']['name'];
			$this->set('title_for_layout', $campaign_result['Campaign']['name']);
			//debug($campaign_result);
		
		$this->set(compact('campaign_result', 'facebook_id', 'meta_description', 'fbmetas', 'already_redeemed', 'campaign', 'already_redeemed'));
		
		if(isset($redeemed_thankyou))	{
			$this->render('claim_thankyou');
		}
	}
	
	public function giftcouponshared() {
		
	}
	
	public function vouchers($action='received') {

		$user_id = $this->Session->read('Auth.User.id');
		
		$fields_default = array('CampaignResult.id', 'Campaign.name', 'Campaign.owner_id', 'CampaignResult.created', 'CampaignResult.coupon_value', 'CampaignResult.status');
		
		//get pending vouchers ;  "Pending" means that it's been shared			
		$fields_pending = array('Creator.full_name');		//additional field full_name of creator as there is no value in Sender or Recipient
		$conditions = array('CampaignResult.creator_id'=>$user_id, 'CampaignResult.parent_id IS NULL', 'CampaignResult.status'=>STATUS_SHARED); //
		$this->CampaignResult->contain(array('Campaign','Creator'));
		$fields = array_merge($fields_default, $fields_pending);
		$vouchers_pending = $this->CampaignResult->find('all', array('conditions'=>$conditions, 'fields'=>$fields));
		
		//get pending vouchers ;  "Pending" means that it's been shared			
		$fields_available = array('Sender.full_name', 'Creator.full_name');		//additional field full_name of creator as there is no value in Sender or Recipient
		$conditions = array('CampaignResult.recepient_id'=>$user_id, 'CampaignResult.status'=>STATUS_USABLE); //
		$this->CampaignResult->contain(array('Campaign','Sender', 'Recepient', 'Creator'));
		$fields = array_merge($fields_default, $fields_available);
		//debug($fields);
		$vouchers_available = $this->CampaignResult->find('all', array('conditions'=>$conditions, 'fields'=>$fields));
		
		//debug($vouchers_available);
		
		//get pending vouchers ;  "Pending" means that it's been shared			
		$fields_used = array('Sender.full_name', 'Creator.full_name');		//additional field full_name of creator as there is no value in Sender or Recipient
		$conditions = array('CampaignResult.recepient_id'=>$user_id, 'CampaignResult.status'=>STATUS_USED); //
		$this->CampaignResult->contain(array('Campaign','Sender', 'Recepient', 'Creator'));
		$fields = array_merge($fields_default, $fields_used);
		//debug($fields);
		$vouchers_used = $this->CampaignResult->find('all', array('conditions'=>$conditions, 'fields'=>$fields));
		
		//debug($vouchers_used);
		
		
		/*switch($action)	{
			case 'shared':
				$conditions = array('CampaignResult.sender_id'=>$user_id, 'CampaignResult.parent_id IS NULL');
			break;
			case 'received':
				$conditions = array('CampaignResult.recepient_id'=>$user_id, 'CampaignResult.sender_id !='=>$user_id,); 
			break;
		}*/
		
		//$this->CampaignResult->recursive = 2;
		$this->CampaignResult->contain(array('Campaign','Recepient','Sender'));
		//$this->CampaignResult->Campaign->contain(array('Owner'));
		//$vouchers = $this->CampaignResult->find('all', array('conditions'=>$conditions));
		
		//debug($vouchers);
		
		$this->set(compact('vouchers_available', 'vouchers_pending', 'vouchers_used'));
		
		//$conditions['CampaignResult.user_id'] = 
		
		//$campaign_result = $this->CampaignResult->find('all', array('conditions'=>$conditions));
		
		
		$this->render('vouchers_tabbed');
		
	}
        
		public function redemption($id, $swipe=null, $confirm=null) {
			
			$user_id = $this->Session->read('Auth.User.id');
			
			$redeemed = false;$giftyType = 'referral';
			
			if(isset($_REQUEST['dd'])) $redeemed = true;
			
			$this->CampaignResult->id = $id;
			//debug($this->request->data);
			if (!$this->CampaignResult->exists()) {
				throw new NotFoundException(__('Invalid'));
			}
			
			$this->CampaignResult->contain(array('Campaign','Recepient', 'Sender'));
			
			$voucher = $this->CampaignResult->read();
			
			//debug($voucher);
			
			if ($voucher['CampaignResult']['recepient_id']!=$user_id) {
				throw new NotFoundException(__('Invalid User'));
			}
			
			if(!is_null($swipe) & !is_null($confirm))	{				
				if($this->request->is('post')) {
					//debug($this->request->data);
					$id = $this->request->data['CampaignResult']['id'];
					$code = $this->request->data['CampaignResult']['code'];
					if(strtoupper($code)=="YES") {						
						//change the status of this gifty
						$data = array('status'=>STATUS_USED);
						$this->CampaignResult->save($data);
						
						//change the status for the sharer of this gifty, if is a child
						if(!is_null($voucher['CampaignResult']['parent_id']))	{
							$this->CampaignResult->id = $voucher['CampaignResult']['parent_id'];
							$data = array('status'=>STATUS_USABLE);
							$this->CampaignResult->save($data);
						}	else	{
							$giftyType = 'reward';
						}
						
						$redeemed = true;
					}	else	{
						$incorrectcode = true;
					}
				}
			}		

			//$conditions = array('CampaignResult.id'=>$coupan_id,'CampaignResult.recepient_id'=>$user_id,'CampaignResult.status'=>STATUS_SHARED);
			//$this->CampaignResult->recursive = 2;
			
			//$this->CampaignResult->Campaign->contain(array('Owner'));
			//$voucher = $this->CampaignResult->read();
			
			//debug($swipe);
			//debug($redeemed);

			$this->set(compact('voucher', 'swipe', 'confirm', 'incorrectcode', 'redeemed', 'giftyType'));
			//debug($voucher);			
			//$campaign_result = $this->CampaignResult->find('all', array('conditions'=>$conditions));
		}
        
		public function redemption_swipe($id) {
			$this->CampaignResult->id = $id;
			if (!$this->CampaignResult->exists()) {
				throw new NotFoundException(__('Invalid'));
			}
			$user_id = $this->Session->read('Auth.User.id');
			//$conditions = array('CampaignResult.id'=>$coupan_id);
			//,'CampaignResult.recepient_id'=>$user_id,'CampaignResult.status'=>STATUS_USABLE
			$this->CampaignResult->contain(array('Campaign','Recepient'));
			$voucher = $this->CampaignResult->read();
			$this->set(compact('voucher'));
		}
    
		public function redemption_swipe_confirm($id) {
			$this->CampaignResult->id = $id;
			if (!$this->CampaignResult->exists()) {
				throw new NotFoundException(__('Invalid'));
			}
			$user_id = $this->Session->read('Auth.User.id');
			if($user_id)	{
				$this->CampaignResult->id = $id;
				$data = array('status'=>STATUS_USED);
				$this->CampaignResult->save($data);
				//$this->CampaignResult->id = $campaign_result_id;
				$campaign = $this->CampaignResult->read();
				$this->set(compact('campaign'));
				$this->render('redemption_swipe_thankyou');
			}
		}
        
	public function update($campaign_result_id, $status) {		
		//debug($this->request->data);		
		$user_id = $this->Session->read('Auth.User.id');
		$sent = false;
		if($user_id)	{			
			if(isset($this->request->data['tos']))	{
				$tos = explode(',', $this->request->data['tos']);
				//debug($tos);
				if(count($tos)>0)	{					
					$this->CampaignResult->id = $campaign_result_id;
					$campaign = $this->CampaignResult->read(array('campaign_id', 'coupon_value'));
					$campaign_id = $campaign['CampaignResult']['campaign_id'];
					//debug($campaign);
					foreach($tos as $fbid)	{
						$count = $this->CampaignResult->find('count', array('conditions'=>array('campaign_id'=>$campaign_id, 'sender_id'=>$user_id, 'recepient_fbid'=>$fbid))); //optional check whether this user has send request for this user for this campaign earlier
						if(!$count>0)	{
							$this->CampaignResult->id = null;
							$data = array('parent_id'=>$campaign_result_id, 'campaign_id'=>$campaign['CampaignResult']['campaign_id'], 'status'=>STATUS_PENDING, 'sender_id'=>$user_id, 'recepient_fbid'=>$fbid, 'coupon_value'=>$campaign['CampaignResult']['coupon_value']); //sender & recipient is self
							//debug($data);
							$this->CampaignResult->save($data);
							$sent = true;
						}
					}
				}
			}
			if($sent)	{
				$this->CampaignResult->id = $campaign_result_id;
				$data = array('status'=>STATUS_SHARED, 'recepient_id'=>$user_id); //sender & recipient is self
				$this->CampaignResult->save($data);
			}
			exit;
		}
	}

}
