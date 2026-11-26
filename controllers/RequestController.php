<?php
// applications and resignations of the orgs
//   apply    (POST) a student applies to join or to be officer
//   resign   (POST) a member asks to leave
//   index    (GET)  the president or admin sees the waiting requests of one org
//   decide   (POST) approve / reject / accept / decline
//
// role ids in the org_role table: 5 = Officer, 6 = Member
class RequestController extends Controller {
    private $orgs;
    private $members;
    private $applications;
    private $resignations;

    function __construct($db) {
        parent::__construct($db);
        $this->orgs = new OrgModel($db);
        $this->members = new MembershipModel($db);
        $this->applications = new ApplicationModel($db);
        $this->resignations = new ResignationModel($db);
    }

    // ---------- student side ----------

    function apply() {
        $user = $this->needRole('student');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->go('organizations');
        }

        $orgId = 0;
        $type = '';
        $reason = '';
        if (isset($_POST['org_id'])) {
            $orgId = (int) $_POST['org_id'];
        }
        if (isset($_POST['type'])) {
            $type = $_POST['type'];
        }
        if (isset($_POST['reason'])) {
            $reason = trim($_POST['reason']);
        }

        $org = $this->orgs->findById($orgId);
        if (!$org) {
            $this->flash('error', 'Organization not found');
            $this->go('organizations');
        }

        // where to go back to after every check
        $back = 'org&id=' . $orgId;


        if ($reason == '' || mb_strlen($reason) > 500) {
            $this->flash('error', 'Please write your reason (max 500 characters)');
            $this->go($back);
        }

        $studentId = $this->members->getStudentId($user['user_id']);
        $current = $this->members->getRole($studentId, $orgId);

        if ($type == 'member') {
            if ($current) {
                $this->flash('error', 'You are already a member of this organization');
                $this->go($back);
            }
            if (!$org['recruiting']) {
                $this->flash('error', 'This organization is not recruiting');
                $this->go($back);
            }
            $roleId = 6;
        }
        else if ($type == 'officer') {
            if (!$current) {
                $this->flash('error', 'Only members can apply for officer');
                $this->go($back);
            }
            if ($current['role_id'] != 6) {
                $this->flash('error', 'You are already an officer');
                $this->go($back);
            }
            if (!$org['officer_hiring']) {
                $this->flash('error', 'Officer hiring is closed');
                $this->go($back);
            }
            $roleId = 5;
        }
        else {
            $this->flash('error', 'Wrong application type');
            $this->go($back);
        }

        if ($this->applications->hasPending($studentId, $orgId, $roleId)) {
            $this->flash('error', 'You already have a pending application');
            $this->go($back);
        }

        $this->applications->create($studentId, $orgId, $roleId, $reason);
        $this->flash('success', 'Application sent');
        $this->go($back);
    }

    function resign() {
        $user = $this->needRole('student');

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->go('organizations');
        }

        $orgId = 0;
        $reason = '';
        if (isset($_POST['org_id'])) {
            $orgId = (int) $_POST['org_id'];
        }
        if (isset($_POST['reason'])) {
            $reason = trim($_POST['reason']);
        }

        $org = $this->orgs->findById($orgId);
        if (!$org) {
            $this->flash('error', 'Organization not found');
            $this->go('organizations');
        }

        $back = 'org&id=' . $orgId;


        $studentId = $this->members->getStudentId($user['user_id']);
        $current = $this->members->getRole($studentId, $orgId);

        if (!$current) {
            $this->flash('error', 'You are not a member of this organization');
            $this->go($back);
        }
        if ($current['role_name'] == 'President') {
            $this->flash('error', 'A president cannot resign, ask an admin to change the president');
            $this->go($back);
        }
        if ($reason == '' || mb_strlen($reason) > 500) {
            $this->flash('error', 'Please write your reason (max 500 characters)');
            $this->go($back);
        }
        if ($this->resignations->hasPending($studentId, $orgId)) {
            $this->flash('error', 'You already have a pending resignation');
            $this->go($back);
        }

        $this->resignations->create($studentId, $orgId, $reason);
        $this->flash('success', 'Resignation form sent');
        $this->go($back);
    }

    // ---------- president / admin side ----------

    // only the president of this org or an admin can review requests
    function index() {
        $user = $this->needLogin();

        $orgId = 0;
        if (isset($_GET['id'])) {
            $orgId = (int) $_GET['id'];
        }

        $org = $this->orgs->findById($orgId);
        if (!$org) {
            $this->flash('error', 'Organization not found');
            $this->go('organizations');
        }

        $this->needOrg($orgId, true);

        $this->view('requests', [
            'org' => $org,
            'applications' => $this->applications->getPending($orgId),
            'resignations' => $this->resignations->getPending($orgId)
        ]);
    }

    // the 4 buttons: kind = application or resignation, answer = yes or no
    function decide() {
        $user = $this->needLogin();

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->go('organizations');
        }

        $kind = '';
        $id = 0;
        $answer = '';
        if (isset($_POST['kind'])) {
            $kind = $_POST['kind'];
        }
        if (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }
        if (isset($_POST['answer'])) {
            $answer = $_POST['answer'];
        }

        if (!in_array($kind, ['application', 'resignation'], true) || !in_array($answer, ['yes', 'no'], true)) {
            $this->flash('error', 'Invalid request action');
            $this->go('organizations');
        }

        // get the request from the database
        if ($kind == 'application') {
            $request = $this->applications->find($id);
        } else {
            $request = $this->resignations->find($id);
        }

        if (!$request) {
            $this->flash('error', 'Request not found');
            $this->go('organizations');
        }

        $orgId = $request['org_id'];
        $back = 'requests&id=' . $orgId;

        $this->needOrg($orgId, true);

        if ($request['status'] != 'pending') {
            $this->flash('error', 'This request was already answered');
            $this->go($back);
        }

        $studentId = $request['student_id'];

        if ($kind == 'application') {
            if ($answer == 'yes') {
                if ($request['role_id'] == 5) {
                    $current = $this->members->getRole($studentId, $orgId);
                    if (!$current || $current['role_name'] !== 'Member') {
                        $this->flash('error', 'Only a current member can be promoted to officer');
                        $this->go($back);
                    }
                    // officer application, the student is already a member so only the role changes
                    $this->members->setRole($studentId, $orgId, 5);
                } elseif ($request['role_id'] == 6) {
                    // member application, only add him if he is not in the org already
                    if (!$this->members->getRole($studentId, $orgId)) {
                        $this->members->add($studentId, $orgId, 6);
                    }
                } else {
                    $this->flash('error', 'Invalid application role');
                    $this->go($back);
                }
                $this->applications->setStatus($id, 'approved');
                $this->flash('success', 'Application approved');
            } else {
                $this->applications->setStatus($id, 'rejected');
                $this->flash('info', 'Application rejected');
            }
        }
        else {
            if ($answer == 'yes') {
                $current = $this->members->getRole($studentId, $orgId);
                if ($current && $current['role_name'] === 'President') {
                    $this->flash('error', 'An admin must replace the president first');
                    $this->go($back);
                }
                $this->members->remove($studentId, $orgId);
                $this->resignations->setStatus($id, 'accepted');
                $this->flash('success', 'Resignation accepted');
            } else {
                $this->resignations->setStatus($id, 'declined');
                $this->flash('info', 'Resignation declined');
            }
        }

        $this->go($back);
    }

}
