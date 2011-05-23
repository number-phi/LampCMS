<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 * 	  the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website's Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2011 (or current year) ExamNotes.net inc.
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */


namespace Lampcms;

use Lampcms\String\HTMLStringParser;

/**
 * Class responsible for parsing submitted
 * answer object.
 * It will create new record in ANSWERS collection,
 * update QUESTIONS collection to increase answer count
 * of parent question, update UNANSWERED_TAGS collection,
 * update user's answers count
 * post event onNewAnswer
 *
 * @author Dmitri Snytkine
 *
 */
class Answerparser extends LampcmsObject
{

	/**
	 * Object of type SubmittedAnswer
	 * (or any sub-class of it)
	 *
	 * @var Object SubmittedAnswer
	 */
	protected $oSubmittedAnswer;


	/**
	 * Object represents question for which
	 * we are processing the answer
	 *
	 * @var object of type Question
	 */
	protected $oQuestion;


	/**
	 * Cache object
	 * @var object of type \Lampcms\Cache
	 */
	protected $oCache;


	/**
	 * Object of newly created answer
	 * an object of type Answer represents one
	 * answer and is a MongoDoc object
	 *
	 * @var object of type Answer (extends MongoDoc object)
	 */
	protected $oAnswer = null;

	
	public function __construct(Registry $oRegistry){
		$this->oRegistry = $oRegistry;
		/**
		 * Need to instantiate Cache so that it
		 * will listen to event and unset some keys
		 */
		$this->oCache = $this->oRegistry->Cache;
		$this->oRegistry->registerObservers('INPUT_FILTERS');
	}


	/**
	 * Getter for oSubmittedAnswer
	 *
	 * @return object of type SubmittedAnswer
	 */
	public function getSubmittedAnswer(){

		return $this->oSubmittedAnswer;
	}


	/**
	 * Getter for $this->oAnswer
	 *
	 * @return mixed object of type Answer or null
	 * if answer object has not yet been created
	 */
	public function getAnswer(){

		return $this->oAnswer;
	}


	/**
	 * Main entry point to parse
	 * submitted answer
	 *
	 * @param object SubmittedAnswer $o
	 * @param object Question $q represents the parent question
	 * this is optional, if not passed, this class will
	 * find parent question based on 'qid' from submitted answer
	 *
	 * @return object of type Answer representing the new
	 * answer (which is also MongoDoc ArrayObject)
	 *
	 */
	public function parse(SubmittedAnswer $o, Question $q = null){

		$this->oSubmittedAnswer = $o;
		$this->oQuestion = (null !== $q) ? $q : $this->getQuestion();

		$this->makeAnswer()
		->followQuestion()
		->updateQuestion();

		return $this->oAnswer;
	}


	/**
	 * Prepare array of data for the answer,
	 * then create oAnswer object from it and
	 * save. It will also fire onBeforeNewAnswer
	 * and onNewAnswer events
	 *
	 * @throws AnswerParserException
	 *
	 * @return object $this
	 */
	protected function makeAnswer(){

		$username = $this->oSubmittedAnswer->getUserObject()->getDisplayName();

		/**
		 * Must pass array('drop-proprietary-attributes' => false)
		 * otherwise tidy removes rel="code"
		 */
		$tidyConfig = ($this->oRegistry->Ini->ENABLE_CODE_EDITOR) ? array('drop-proprietary-attributes' => false) : null;
		$oBody = $this->oSubmittedAnswer->getBody()->tidy($tidyConfig)->safeHtml()->asHtml();

		$htmlBody = HTMLStringParser::factory($oBody)->parseCodeTags()->linkify()->importCDATA()->setNofollow()->valueOf();

		d('after HTMLStringParser: '.$htmlBody);

		$username = $this->oSubmittedAnswer->getUserObject()->getDisplayName();
		$uid = $this->oSubmittedAnswer->getUserObject()->getUid();
		$qid = $this->oSubmittedAnswer->getQid();

		$hash = hash('md5', \mb_strtolower($htmlBody.$qid));

		/**
		 * 
		 * We need to copy the title
		 * here too because Answer by itself does not have own
		 * title but we need a title when displaying links
		 * to answer on profile pages
		 *
		 * @todo later can also parse for smilies here
		 *
		 */
		$this->checkForDuplicate($hash);

		$aData = array(
		'_id' => $this->oRegistry->Resource->create('ANSWER'),
		'i_qid' => $qid,
		'i_uid' => $uid,
		'i_quid' => $this->oQuestion->getOwnerId(),
		'title' => $this->oQuestion->getTitle(),	
		'hash' => $hash,
		'username' => $username,
		'ulink' => '<a href="'.$this->oSubmittedAnswer->getUserObject()->getProfileUrl().'">'.$username.'</a>',
		'avtr' => $this->oSubmittedAnswer->getUserObject()->getAvatarSrc(),
		'i_words' => $oBody->asPlainText()->getWordsCount(),
		'i_up' => 0,
		'i_down' => 0,
		'i_votes' => 0,
		'b' => $htmlBody,
		'i_ts' => time(),
		'i_lm_ts' => time(),
		'hts' => date('F j, Y g:i a T'),
		'v_s' => 's',
		'accepted' => false,
		'ip' => $this->oSubmittedAnswer->getIP(),
		'app' => 'web'
		);

		d('cp');

		/**
		 * Submitted answer object may provide
		 * extra elements to be added to aData array
		 * This is usually useful for parsing answers that
		 * came from external API
		 *
		 * as well as adding 'credit' div
		 */
		$aExtraData = $this->oSubmittedAnswer->getExtraData();
		d('$aExtraData: '.print_r($aExtraData, 1));
		if(!empty($aExtraData)){
			$aData = array_merge($aData, $aExtraData);
		}
		d('$aData: '.print_r($aData, 1));

		$this->oAnswer = new Answer($this->oRegistry, $aData);

		/**
		 * Post onBeforeNewQuestion event
		 * and watch for filter either cancelling the event
		 * or throwing FilterException (prefferred way because
		 * a specific error message can be passed in FilterException
		 * this way)
		 *
		 * In either case we throw QuestionParserException
		 * Controller that handles the question form should be ready
		 * to handle this exception and set the form error using
		 * message from exception. This way the error will be shown to
		 * the user right on the question form while question form's data
		 * is preserved in form.
		 */
		try {
			$oNotification = $this->oRegistry->Dispatcher->post($this->oAnswer, 'onBeforeNewAnswer');
			if($oNotification->isNotificationCancelled()){
				throw new AnswerParserException('Sorry, we are unable to process your answer at this time.');
			}
		} catch (FilterException $e){
			e('Got filter exteption: '.$e->getFile().' '.$e->getLine().' '.$e->getMessage().' '.$e->getTraceAsString());
			throw new AnswerParserException($e->getMessage());
		}

		/**
		 * Do ensureIndexes() now and not before we are sure that we even going
		 * to add a new question.
		 */
		$this->ensureIndexes();

		$this->oAnswer->insert();

		$this->oRegistry->Dispatcher->post($this->oAnswer, 'onNewAnswer', array('question' => $this->oQuestion));

		/**
		 * Reuse $uid since we already resolved it here,
		 * so no need to go through the same
		 * $this->oSubmittedAnswer->getUserObject()->getUid() again
		 */
		$this->addUserTags($uid);

		return $this;
	}


	/**
	 * Ensure indexes in all collections involved
	 * in storing question data
	 *
	 * @return object $this
	 */
	protected function ensureIndexes(){
		$ans = $this->oRegistry->Mongo->ANSWERS;
		/**
		 * There is no reason to index by original timestamp
		 * (i_ts) because if we want to order by added time
		 * we can just sort by _id since value
		 * of _id in already in the order from oldest to newest
		 * (which is a primary key and alwasy indexed anyway)
		 */
		$ans->ensureIndex(array('i_lm_ts' => 1));
		$ans->ensureIndex(array('i_votes' => 1));
		$ans->ensureIndex(array('i_uid' => 1));
		$ans->ensureIndex(array('i_qid' => 1));
		$ans->ensureIndex(array('hash' => 1), array('unique' => true));
		/**
		 * Index by ip address will help when we need to find
		 * all posts from the same ip which we need for
		 * flood check
		 */
		$ans->ensureIndex(array('ip' => 1));

		return $this;
	}


	/**
	 * Detect exact same answer submitted for same
	 * question, regardless of who submitted the answer
	 *
	 * Even when submitted by 2 different users, duplicate answers
	 * are not allowed
	 *
	 * @return object $this
	 */
	protected function checkForDuplicate($hash){
		$a = $this->oRegistry->Mongo->ANSWERS->findOne(array('hash' => $hash));
		if(!empty($a)){
			throw new AnswerParserException('Someone (possibly you) has already added exact same answer for this question. Duplicate answers are not allowed');
		}

		return $this;
	}


	/**
	 * Increase answer count
	 * for question.
	 * Also set Last Answerer details
	 * and add Answerer User to list
	 * of Question contributors
	 * (this is for the dot-folders feature)
	 *
	 * The increaseAnswerCount will also update
	 * the last modified timestamp for question
	 *
	 * @return object $this
	 */
	protected function updateQuestion(){

		$oUser = $this->oSubmittedAnswer->getUserObject();

		$this->oQuestion->updateAnswerCount()
		->addContributor($oUser)
		->setLatestAnswer($oUser, $this->oAnswer)
		->touch();

		return $this;
	}


	/**
	 * Answer author will automatically
	 * start following this question
	 *
	 * @return object $this
	 */
	protected function followQuestion(){
		$oFollowManager = new FollowManager($this->oRegistry);
		$oFollowManager->followQuestion($this->oRegistry->Viewer, $this->oQuestion);

		return $this;
	}


	/**
	 * Updates USER_TAGS collection
	 * Takes into account the tags from the
	 * Question for which the user just submitted
	 * an answer.
	 * This is run via shutdown function
	 *
	 * @return object $this
	 */
	protected function addUserTags($uid){

		$oTags = UserTags::factory($this->oRegistry);
		$oQuestion = $this->oQuestion;

		$func = function() use ($oTags, $uid, $oQuestion){
			$oTags->addTags($uid, $oQuestion);
		};
		d('cp');
		runLater($func);
		d('cp');

		return $this;
	}


	/**
	 * Getter for $this->oQuestion
	 *
	 * @return object of type Question representing the Question
	 * for which we parsing the answer
	 */
	public function getQuestion(){
		if(!isset($this->oQuestion)){
			$a = $this->oRegistry->Mongo->QUESTIONS->findOne(array('_id' => $this->oSubmittedAnswer->getQid()));

			if(empty($a)){
				e('Cannot find question with _id: '.$this->oAnswer['qid']);

				throw new Exception('Unable to find parent question for this answer');
			}

			$this->oQuestion = new Question($this->oRegistry, $a);
		}

		return $this->oQuestion;
	}

}

