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


/**
 * Class for generating and
 * maintaining records of per-collection
 * auto-increment ids
 *
 * @author Dmitri Snytkine
 *
 */
class MongoIncrementor
{
	protected $oMongoDB;

	public function __construct(Mongo $oMongo){
		$this->oMongoDB = $oMongo->getDb();
	}

	/**
	 * Name of collection where to
	 * store the auto-increment values
	 * You can change it but only before you
	 * store your first value.
	 * Once you begin storing values of you
	 * auto-increments, it's best not to change this, ever!
	 *
	 * @var string name of collection
	 */
	const COLLECTION_NAME = 'Autoincrements';

	/**
	 * The pseudo auto increment handling is done
	 * by storing collectionName => id
	 * in Autoincrements collection
	 *
	 * We get value, increment it and resave it
	 * but watch for Errors/Exceptions in order
	 * to prevent race condition
	 *
	 * @param string $collName which collection this id
	 * is for. This has nothing to do with the name of the collection
	 * where these generated sequence numbers are stored.
	 * For example if you need the next id for collection 'STUDENTS',
	 * then you pass the 'STUDENTS' as $collName value
	 * This way different values of 'next id' are maintained
	 * per collection name
	 *
	 * @param int $initialId if there is no record
	 * for the collection yet, then start the increment counter
	 * with this value.
	 *
	 * @param int $try this is used for recursive calling this method
	 * You should NEVER pass this value yourself
	 *
	 * @return int value of next id for the collection
	 */
	public function nextValue($collName, $initialId = 0, $try = 1){

		if(  $try > 100 ){
			throw new \RuntimeException('Unable to get nextID for collection '.$collName.' after 100 tries');
		}

		$prevRecordID = null;
		$coll = $this->oMongoDB->selectCollection(self::COLLECTION_NAME);
		$coll->ensureIndex(array('coll' => 1, 'id' => 1), array('unique' => true));

		/**
		 * We use find() instead of findOne() for a reason!
		 * It's just more reliable this way
		 */
		$cursor = $coll->find(array('coll' => $collName))->sort(array('id' => -1))->limit(1);
		if($cursor && $cursor->hasNext()){
			$a = $cursor->getNext();
			$prevRecordID = $a['_id'];
		} else {
			$a = array('coll' => $collName, 'id' => $initialId);
		}

		$prevID = $a['id'];
		$newId = ($a['id'] + 1);

		/**
		 * Remove the _id from record, otherwise
		 * we will be unable to insert
		 * a new record if it already has the same _id
		 * This way a new _id will be auto-generated for us
		 */
		unset($a['_id']);
		$a['id'] = $newId;


		/**
		 * Wrapping this inside try/catch so that if
		 * another process inserts the same value of coll/id
		 * between the time we selected and updated this
		 * it will throw exception or return false and then
		 * we will try again up to 100 times
		 *
		 * In Case of duplicate key Mongo throws Exception,
		 * but just in case it will change in the future,
		 * we also test if $ret is false
		 */
		try{
			/**
			 * Using fsync=>true because its very critically important
			 * to actually write the row to disc, otherwise if database
			 * goes down we will lose the correct value
			 * of our increment ID
			 */
			$ret = $coll->insert($a, array('fsync' => true));
			if(!$ret){
				$try++;

				return $this->nextValue($collName, $initialId, $try);
			}

			/**
			 * Insert successfull
			 * now delete previous record(s)
			 */
			if(null !== $prevRecordID){
				$removed = $coll->remove(array('_id' => $prevRecordID)); //, array('fsync' => true) // not very important to fsync
			}

		} catch (\MongoException $e){

			$try++;

			return $this->nextValue($collName, $initialId, $try);
		}

		return $newId;
	}
}
