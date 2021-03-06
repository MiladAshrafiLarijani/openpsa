<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_services_auth_aclTest extends openpsa_testcase
{
    public function test_can_do_parent_object_privilege()
    {
        $topic = $this->create_object('midcom_db_topic');
        $article = $this->create_object('midcom_db_article', array('topic' => $topic->id));

        $topic_denied = $this->create_object('midcom_db_topic');
        $article_denied = $this->create_object('midcom_db_article', array('topic' => $topic_denied->id));
        $person = $this->create_user();

        midcom::get()->auth->request_sudo('midcom.core');
        $person->set_privilege('midgard:read', 'SELF', MIDCOM_PRIVILEGE_DENY, 'midcom_db_article');
        $topic_denied->set_privilege('midgard:read', 'user:' . $person->guid, MIDCOM_PRIVILEGE_DENY);

        $user = new midcom_core_user($person);
        midcom::get()->auth->drop_sudo();

        $auth = new midcom_services_auth;

        $this->assertTrue($auth->can_do('midgard:read', $article));
        $this->assertTrue($auth->can_do('midgard:read', $topic));
        $this->assertTrue($auth->can_do('midgard:read', $article_denied));

        $auth->user = $user;
        $this->assertTrue($auth->can_do('midgard:read', $topic));
        $this->assertFalse($auth->can_do('midgard:read', $article));
        $this->assertFalse($auth->can_do('midgard:read', $article_denied));

        $person2 = $this->create_user();
        $user2 = new midcom_core_user($person2);
        $auth->user = $user2;

        $this->assertTrue($auth->can_do('midgard:read', $article));
        $this->assertTrue($auth->can_do('midgard:read', $article_denied));
    }

    public function test_can_do_group_privilege()
    {
        $topic = $this->create_object('midcom_db_topic');
        $person = $this->create_user();
        $group = $this->create_object('midcom_db_group');
        $this->create_object('midcom_db_member', array('gid' => $group->id, 'uid' => $person->id));

        midcom::get()->auth->request_sudo('midcom.core');
        $topic->set_privilege('midgard:read', 'group:' . $group->guid, MIDCOM_PRIVILEGE_DENY);
        $user = new midcom_core_user($person);
        midcom::get()->auth->drop_sudo();

        $auth = new midcom_services_auth;

        $this->assertTrue($auth->can_do('midgard:read', $topic));

        $auth->user = $user;
        $this->assertFalse($auth->can_do('midgard:read', $topic));
    }
}
