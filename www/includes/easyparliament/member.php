<?php

include_once INCLUDESPATH."postcode.inc";
include_once INCLUDESPATH."easyparliament/glossary.php";

class MEMBER {

    public $valid = false;
    public $member_id;
    public $person_id;
    public $title;
    public $given_name;
    public $family_name;
    public $lordofname;
    public $constituency;
    public $party;
    public $other_parties = array();
    public $other_constituencies;
    public $houses = array();
    public $entered_house = array();
    public $left_house = array();
    public $extra_info = array();
    // Is this MP THEUSERS's MP?
    public $the_users_mp = false;
    public $house_disp = 0; # Which house we should display this person in

    // Mapping member table 'house' numbers to text.
    public $houses_pretty = array(
        0 => 'Royal Family',
        1 => 'House of Commons',
        2 => 'House of Lords',
        3 => 'Northern Ireland Assembly',
        4 => 'Scottish Parliament',
    );

    // Mapping member table reasons to text.
    public $reasons = array(
        'became_peer'		=> 'Became peer',
        'by_election'		=> 'Byelection',
        'changed_party'		=> 'Changed party',
        'changed_name' 		=> 'Changed name',
        'declared_void'		=> 'Declared void',
        'died'			=> 'Died',
        'disqualified'		=> 'Disqualified',
        'general_election' 	=> 'General election',
        'general_election_standing' 	=> array('General election (standing again)', 'General election (stood again)'),
        'general_election_not_standing' 	=> 'did not stand for re-election',
        'reinstated'		=> 'Reinstated',
        'resigned'		=> 'Resigned',
        'still_in_office'	=> 'Still in office',
        'dissolution'		=> 'Dissolved for election',
        'regional_election'	=> 'Election', # Scottish Parliament
        'replaced_in_region'	=> 'Appointed, regional replacement',

    );

    public function MEMBER($args) {
        // $args is a hash like one of:
        // member_id 		=> 237
        // person_id 		=> 345
        // constituency 	=> 'Braintree'
        // postcode			=> 'e9 6dw'

        // If just a constituency we currently just get the current member for
        // that constituency.

        global $this_page;

        $house = isset($args['house']) ? $args['house'] : null;

        $this->db = new ParlDB;
        $person_id = '';
        if (isset($args['member_id']) && is_numeric($args['member_id'])) {
            $person_id = $this->member_id_to_person_id($args['member_id']);
        } elseif (isset($args['name'])) {
            $con = isset($args['constituency']) ? $args['constituency'] : '';
            $person_id = $this->name_to_person_id($args['name'], $con);
        } elseif (isset($args['constituency'])) {
            $still_in_office = isset($args['still_in_office']) ? $args['still_in_office'] : false;
            $person_id = $this->constituency_to_person_id($args['constituency'], $house, $still_in_office);
        } elseif (isset($args['postcode'])) {
            $person_id = $this->postcode_to_person_id($args['postcode'], $house);
        } elseif (isset($args['person_id']) && is_numeric($args['person_id'])) {
            $person_id = $args['person_id'];
            $q = $this->db->query("SELECT gid_to FROM gidredirect
                    WHERE gid_from = :gid_from",
                array(':gid_from' => "uk.org.publicwhip/person/$person_id")
            );
            if ($q->rows > 0) {
                $person_id = str_replace('uk.org.publicwhip/person/', '', $q->field(0, 'gid_to'));
            }
        }

        if (!$person_id) {
            return;
        }

        $q = $this->db->query("SELECT member_id, house, title,
            given_name, family_name, lordofname, constituency, party, lastupdate,
            entered_house, left_house, entered_reason, left_reason, member.person_id
            FROM member, person_names pn
            WHERE member.person_id = :person_id
                AND member.person_id = pn.person_id AND pn.type = 'name' AND pn.start_date <= left_house AND left_house <= pn.end_date
            ORDER BY left_house DESC, house", array(
                ':person_id' => $person_id
            ));

        if (!$q->rows() > 0) {
            return;
        }

        $this->valid = true;

        $this->house_disp = 0;
        $last_party = null;
        for ($row=0; $row<$q->rows(); $row++) {
            $house          = $q->field($row, 'house');
            if (!in_array($house, $this->houses)) $this->houses[] = $house;
            $const          = $q->field($row, 'constituency');
            $party		= $q->field($row, 'party');
            $entered_house	= $q->field($row, 'entered_house');
            $left_house	= $q->field($row, 'left_house');
            $entered_reason	= $q->field($row, 'entered_reason');
            $left_reason	= $q->field($row, 'left_reason');

            if (!isset($this->entered_house[$house]) || $entered_house < $this->entered_house[$house]['date']) {
                $this->entered_house[$house] = array(
                    'date' => $entered_house,
                    'date_pretty' => $this->entered_house_text($entered_house),
                    'reason' => $this->entered_reason_text($entered_reason),
                );
            }

            if (!isset($this->left_house[$house])) {
                $this->left_house[$house] = array(
                    'date' => $left_house,
                    'date_pretty' => $this->left_house_text($left_house),
                    'reason' => $this->left_reason_text($left_reason, $left_house, $house),
                    'constituency' => $const,
                    'party' => $this->party_text($party)
                );
            }

            if ( $house==HOUSE_TYPE_ROYAL 					# The Monarch
                || (!$this->house_disp && $house==HOUSE_TYPE_SCOTLAND)	# MSPs and
                || (!$this->house_disp && $house==HOUSE_TYPE_NI)	# MLAs have lowest priority
                || ($this->house_disp!=HOUSE_TYPE_LORDS && $house==HOUSE_TYPE_LORDS)	# Lords have highest priority
                || (!$this->house_disp && $house==HOUSE_TYPE_COMMONS) # MPs
            ) {
                $this->house_disp = $house;
                $this->constituency = $const;
                $this->party = $party;

                $this->member_id	= $q->field($row, 'member_id');
                $this->title		= $q->field($row, 'title');
                $this->given_name = $q->field($row, 'given_name');
                $this->family_name = $q->field($row, 'family_name');
                $this->lordofname = $q->field($row, 'lordofname');
                $this->person_id	= $q->field($row, 'person_id');
            }

            if (($last_party && $party && $party != $last_party) || $left_reason == 'changed_party') {
                $this->other_parties[] = array(
                    'from' => $this->party_text($party),
                    'date' => $left_house,
                );
            }
            $last_party = $party;

            if ($const != $this->constituency) {
                $this->other_constituencies[$const] = true;
            }
        }
        $this->other_parties = array_reverse($this->other_parties);

        // Loads extra info from DB - you now have to call this from outside
            // when you need it, as some uses of MEMBER are lightweight (e.g.
            // in searchengine.php)
        // $this->load_extra_info();

        $this->set_users_mp();
    }

    public function member_id_to_person_id($member_id) {
        $q = $this->db->query("SELECT person_id FROM member
                    WHERE member_id = :member_id",
            array(':member_id' => $member_id)
        );
        if ($q->rows == 0) {
            $q = $this->db->query("SELECT person_id FROM gidredirect, member
                    WHERE gid_from = :gid_from AND
                        CONCAT('uk.org.publicwhip/member/', member_id) = gid_to",
                array(':gid_from' => "uk.org.publicwhip/member/$member_id")
            );
        }
        if ($q->rows > 0) {
            return $q->field(0, 'person_id');
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, there is no member with a member ID of "' . _htmlentities($member_id) . '".');
        }
    }

    public function postcode_to_person_id($postcode, $house=null) {
        twfy_debug ('MP', "postcode_to_person_id converting postcode to person");
        $constituency = strtolower(postcode_to_constituency($postcode));
        return $this->constituency_to_person_id($constituency, $house);
    }

    public function constituency_to_person_id($constituency, $house=null) {
        if ($constituency == '') {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, no constituency was found.');
        }

        if ($constituency == 'Orkney ') {
            $constituency = 'Orkney & Shetland';
        }

        $normalised = normalise_constituency_name($constituency);
        if ($normalised) $constituency = $normalised;

        $params = array();

        $left = "left_reason = 'still_in_office'";
        if (DISSOLUTION_DATE) {
            $left = "($left OR left_house = '" . DISSOLUTION_DATE . "')";
        }
        $query = "SELECT person_id FROM member
                WHERE constituency = :constituency
                AND $left";

        $params[':constituency'] = $constituency;

        if ($house) {
            $query .= ' AND house = :house';
            $params[':house'] = $house;
        }

        $q = $this->db->query($query, $params);

        if ($q->rows > 0) {
            return $q->field(0, 'person_id');
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, there is no current member for the "' . _htmlentities(ucwords($constituency)) . '" constituency.');
        }
    }

    public function name_to_person_id($name, $const='') {
        global $this_page;
        if ($name == '') {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, no name was found.');
        }

        $params = array();
        $q = "SELECT person_id FROM person_names WHERE type = 'name' ";
        if ($this_page == 'peer') {
            $success = preg_match('#^(.*?) (.*?) of (.*?)$#', $name, $m);
            if (!$success)
                $success = preg_match('#^(.*?)() of (.*?)$#', $name, $m);
            if (!$success)
                $success = preg_match('#^(.*?) (.*?)()$#', $name, $m);
            if (!$success) {
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, that name was not recognised.');
            }
            $params[':title'] = $m[1];
            $params[':family_name'] = $m[2];
            $params[':lordofname'] = $m[3];
            $q .= "AND title = :title AND family_name = :family_name AND lordofname = :lordofname";
        } elseif ($this_page == 'msp' || $this_page == 'mla' || strstr($this_page, 'mp')) {
            $success = preg_match('#^(.*?) (.*?) (.*?)$#', $name, $m);
            if (!$success)
                $success = preg_match('#^(.*?)() (.*)$#', $name, $m);
            if (!$success) {
                throw new MySociety\TheyWorkForYou\MemberException('Sorry, that name was not recognised.');
            }
            $params[':given_name'] = $m[1];
            $params[':middle_name'] = $m[2];
            $params[':family_name'] = $m[3];
            $params[':first_and_middle_names'] = $m[1] . ' ' . $m[2];
            $params[':middle_and_last_names'] = $m[2] . ' ' . $m[3];
            # Note this works only because MySQL ignores trailing whitespace
            $q .= "AND (
                (given_name=:first_and_middle_names AND family_name=:family_name)
                OR (given_name=:given_name AND family_name=:middle_and_last_names)
                OR (title=:given_name AND given_name=:middle_name AND family_name=:family_name)
            )";
        }

        $q = $this->db->query($q, $params);
        if (!$q->rows) {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, we could not find anyone with that name.');
        } elseif ($q->rows == 1) {
            return $q->field(0, 'person_id');
        }

        # More than one person ID matching the given name
        $person_ids = array();
        for ($i=0; $i<$q->rows; ++$i) {
            $pid = $q->field($i, 'person_id');
            $person_ids[$pid] = 1;
        }
        $pids = array_keys($person_ids);

        $params = array();
        if ($this_page == 'peer') {
            $params[':house'] = HOUSE_TYPE_LORDS;
        } elseif ($this_page == 'msp') {
            $params[':house'] = HOUSE_TYPE_SCOTLAND;
        } elseif ($this_page == 'mla') {
            $params[':house'] = HOUSE_TYPE_NI;
        } elseif ($this_page == 'royal') {
            $params[':house'] = HOUSE_TYPE_ROYAL;
        } else {
            $params[':house'] = HOUSE_TYPE_COMMONS;
        }

        $pids_str = join(',', $pids);
        $q = "SELECT person_id, constituency FROM member WHERE person_id IN ($pids_str) AND house = :house";
        if ($const) {
            $params[':constituency'] = $const;
            $q .= ' AND constituency=:constituency';
        }
        $q .= ' GROUP BY person_id';

        $q = $this->db->query($q, $params);
        if ($q->rows > 1) {
            $person_ids = array();
            for ($i=0; $i<$q->rows(); ++$i) {
                $person_ids[$q->field($i, 'person_id')] = $q->field($i, 'constituency');
            }
            throw new MySociety\TheyWorkForYou\MemberMultipleException($person_ids);
        } elseif ($q->rows > 0) {
            return $q->field(0, 'person_id');
        } elseif ($const) {
            return $this->name_to_person_id($name);
        } else {
            throw new MySociety\TheyWorkForYou\MemberException('Sorry, there is no current member with that name.');
        }
    }

    public function set_users_mp() {
        // Is this MP THEUSER's MP?
        global $THEUSER;
        if (is_object($THEUSER) && $THEUSER->postcode_is_set() && $this->current_member(1)) {
            $pc = $THEUSER->postcode();
            twfy_debug ('MP', "set_users_mp converting postcode to person");
            $constituency = strtolower(postcode_to_constituency($pc));
            if ($constituency == strtolower($this->constituency())) {
                $this->the_users_mp = true;
            }
        }
    }

    // Grabs extra information (e.g. external links) from the database
    # DISPLAY is whether it's to be displayed on MP page.
    public function load_extra_info($display = false) {
        $memcache = new MySociety\TheyWorkForYou\Memcache;
        $memcache_key = 'extra_info:' . $this->person_id . ($display ? '' : ':plain');
        $this->extra_info = $memcache->get($memcache_key);
        if (!DEVSITE && $this->extra_info) {
            return;
        }
        $this->extra_info = array();

        $q = $this->db->query('SELECT * FROM moffice WHERE person=:person_id ORDER BY from_date DESC',
                              array(':person_id' => $this->person_id));
        for ($row=0; $row<$q->rows(); $row++) {
            $this->extra_info['office'][] = $q->row($row);
        }

        // Info specific to member id (e.g. attendance during that period of office)
        $q = $this->db->query("SELECT data_key, data_value
                        FROM 	memberinfo
                        WHERE	member_id = :member_id",
            array(':member_id' => $this->member_id));
        for ($row = 0; $row < $q->rows(); $row++) {
            $this->extra_info[$q->field($row, 'data_key')] = $q->field($row, 'data_value');
            #		if ($q->field($row, 'joint') > 1)
            #			$this->extra_info[$q->field($row, 'data_key').'_joint'] = true;
        }

        // Info specific to person id (e.g. their permanent page on the Guardian website)
        $q = $this->db->query("SELECT data_key, data_value
                        FROM 	personinfo
                        WHERE	person_id = :person_id",
            array(':person_id' => $this->person_id));
        for ($row = 0; $row < $q->rows(); $row++) {
            $this->extra_info[$q->field($row, 'data_key')] = $q->field($row, 'data_value');
        #	    if ($q->field($row, 'count') > 1)
        #	    	$this->extra_info[$q->field($row, 'data_key').'_joint'] = true;
        }

        // Info specific to constituency (e.g. election results page on Guardian website)
        if ($this->house(HOUSE_TYPE_COMMONS)) {

            $q = $this->db->query("SELECT data_key, data_value FROM consinfo
            WHERE constituency = :constituency",
                array(':constituency' => $this->constituency));
            for ($row = 0; $row < $q->rows(); $row++) {
                $this->extra_info[$q->field($row, 'data_key')] = $q->field($row, 'data_value');
            }
        }

        if (array_key_exists('public_whip_rebellions', $this->extra_info)) {
            $rebellions = $this->extra_info['public_whip_rebellions'];
            $rebel_desc = "<unknown>";
            if ($rebellions == 0)
                $rebel_desc = "never";
            elseif ($rebellions <= 1)
                $rebel_desc = "hardly ever";
            elseif ($rebellions <= 3)
                $rebel_desc = "occasionally";
            elseif ($rebellions <= 5)
                $rebel_desc = "sometimes";
            elseif ($rebellions > 5)
                $rebel_desc = "quite often";
            $this->extra_info['public_whip_rebel_description'] = $rebel_desc;
        }

        if (isset($this->extra_info['public_whip_attendrank'])) {
            $prefix = ($this->house(HOUSE_TYPE_LORDS) ? 'L' : '');
            $this->extra_info[$prefix.'public_whip_division_attendance_rank'] = $this->extra_info['public_whip_attendrank'];
            $this->extra_info[$prefix.'public_whip_division_attendance_rank_outof'] = $this->extra_info['public_whip_attendrank_outof'];
            $this->extra_info[$prefix.'public_whip_division_attendance_quintile'] = floor($this->extra_info['public_whip_attendrank'] / ($this->extra_info['public_whip_attendrank_outof']+1) * 5);
        }
        if ($this->house(HOUSE_TYPE_LORDS) && isset($this->extra_info['public_whip_division_attendance'])) {
            $this->extra_info['Lpublic_whip_division_attendance'] = $this->extra_info['public_whip_division_attendance'];
            unset($this->extra_info['public_whip_division_attendance']);
        }

        if ($display && array_key_exists('register_member_interests_html', $this->extra_info) && ($this->extra_info['register_member_interests_html'] != '')) {
            $args = array (
                "sort" => "regexp_replace"
            );
            $GLOSSARY = new GLOSSARY($args);
            $this->extra_info['register_member_interests_html'] =
        $GLOSSARY->glossarise($this->extra_info['register_member_interests_html']);
        }

        $q = $this->db->query('select count(*) as c from alerts where criteria like "%speaker:'.$this->person_id.'%" and confirmed and not deleted');
        $this->extra_info['number_of_alerts'] = $q->field(0, 'c');

        if (isset($this->extra_info['reading_ease'])) {
            $this->extra_info['reading_ease'] = round($this->extra_info['reading_ease'], 2);
            $this->extra_info['reading_year'] = round($this->extra_info['reading_year'], 0);
            $this->extra_info['reading_age'] = $this->extra_info['reading_year'] + 4;
            $this->extra_info['reading_age'] .= '&ndash;' . ($this->extra_info['reading_year'] + 5);
        }

        # Public Bill Committees
        $q = $this->db->query('select bill_id,session,title,sum(attending) as a,sum(chairman) as c
            from pbc_members, bills
            where bill_id = bills.id and person_id = ' . $this->person_id()
             . ' group by bill_id order by session desc');
        $this->extra_info['pbc'] = array();
        for ($i=0; $i<$q->rows(); $i++) {
            $bill_id = $q->field($i, 'bill_id');
            $c = $this->db->query('select count(*) as c from hansard where major=6 and minor='.$bill_id.' and htype=10');
            $c = $c->field(0, 'c');
            $title = $q->field($i, 'title');
            $attending = $q->field($i, 'a');
            $chairman = $q->field($i, 'c');
            $this->extra_info['pbc'][$bill_id] = array(
                'title' => $title, 'session' => $q->field($i, 'session'),
                'attending'=>$attending, 'chairman'=>($chairman>0), 'outof' => $c
            );
        }

        $memcache->set($memcache_key, $this->extra_info);
    }

    // Functions for accessing things about this Member.

    public function member_id() { return $this->member_id; }
    public function person_id() { return $this->person_id; }
    public function given_name() { return $this->given_name; }
    public function family_name() { return $this->family_name; }
    public function full_name($no_mp_title = false) {
        $title = $this->title;
        if ($no_mp_title && ($this->house_disp==HOUSE_TYPE_COMMONS || $this->house_disp==HOUSE_TYPE_NI || $this->house_disp==HOUSE_TYPE_SCOTLAND))
            $title = '';
        return member_full_name($this->house_disp, $title, $this->given_name, $this->family_name, $this->lordofname);
    }
    public function houses() {
        return $this->houses;
    }
    public function house($house) {
        return in_array($house, $this->houses) ? true : false;
    }
    public function house_text($house) {
        return $this->houses_pretty[$house];
    }
    public function constituency() { return $this->constituency; }
    public function party() { return $this->party; }
    public function party_text($party = null) {
        global $parties;
        if (!$party)
            $party = $this->party;
        if (isset($parties[$party])) {
            return $parties[$party];
        } else {
            return $party;
        }
    }

    public function entered_house($house = 0) {
        if ($house) return array_key_exists($house, $this->entered_house) ? $this->entered_house[$house] : null;
        return $this->entered_house;
    }
    public function entered_house_text($entered_house) {
        if (!$entered_house) return '';
        list($year, $month, $day) = explode('-', $entered_house);
        if ($month==1 && $day==1 && $this->house(HOUSE_TYPE_LORDS)) {
            return $year;
        } elseif ($month==0 && $day==0) {
            return $year;
        } elseif (checkdate($month, $day, $year) && $year != '9999') {
            return format_date($entered_house, LONGDATEFORMAT);
        } else {
            return "n/a";
        }
    }

    public function left_house($house = null) {
        if (!is_null($house))
            return array_key_exists($house, $this->left_house) ? $this->left_house[$house] : null;
        return $this->left_house;
    }

    public function left_house_text($left_house) {
        if (!$left_house) return '';
        list($year, $month, $day) = explode('-', $left_house);
        if (checkdate($month, $day, $year) && $year != '9999') {
            return format_date($left_house, LONGDATEFORMAT);
        } elseif ($month==0 && $day==0) {
            # Left house date is stored as 1942-00-00 to mean "at some point in 1941"
            return $year - 1;
        } else {
            return "n/a";
        }
    }

    public function entered_reason() { return $this->entered_reason; }
    public function entered_reason_text($entered_reason) {
        if (isset($this->reasons[$entered_reason])) {
            return $this->reasons[$entered_reason];
        } else {
            return $entered_reason;
        }
    }

    public function left_reason() { return $this->left_reason; }
    public function left_reason_text($left_reason, $left_house, $house) {
        if (isset($this->reasons[$left_reason])) {
            $left_reason = $this->reasons[$left_reason];
            if (is_array($left_reason)) {
                $q = $this->db->query("SELECT MAX(left_house) AS max FROM member WHERE house=$house");
                $max = $q->field(0, 'max');
                if ($max == $left_house) {
                    return $left_reason[0];
                } else {
                    return $left_reason[1];
                }
            } else {
                return $left_reason;
            }
        } else {
            return $left_reason;
        }
    }

    public function extra_info() { return $this->extra_info; }

    public function current_member($house = 0) {
        $current = array();
        foreach (array_keys($this->houses_pretty) as $h) {
            $lh = $this->left_house($h);
            $current[$h] = ($lh['date'] == '9999-12-31');
        }
        if ($house) return $current[$house];
        return $current;
    }

    public function the_users_mp() { return $this->the_users_mp; }

    public function url($absolute = true) {
        $house = $this->house_disp;
        if ($house == HOUSE_TYPE_COMMONS) {
            $URL = new URL('mp');
        } elseif ($house == HOUSE_TYPE_LORDS) {
            $URL = new URL('peer');
        } elseif ($house == HOUSE_TYPE_NI) {
            $URL = new URL('mla');
        } elseif ($house == HOUSE_TYPE_SCOTLAND) {
            $URL = new URL('msp');
        } elseif ($house == HOUSE_TYPE_ROYAL) {
            $URL = new URL('royal');
        }
        $member_url = make_member_url($this->full_name(true), $this->constituency(), $house, $this->person_id());
        if ($absolute)
            return 'http://' . DOMAIN . $URL->generate('none') . $member_url;
        else
            return $URL->generate('none') . $member_url;
    }

    private function _previous_future_mps_query($direction) {
        $entered_house = $this->entered_house(HOUSE_TYPE_COMMONS);
        if (is_null($entered_house)) return '';
        if ($direction == '>') {
            $order = '';
        } else {
            $order = 'DESC';
        }
        $q = $this->db->query('SELECT *
            FROM member, person_names pn
            WHERE member.person_id = pn.person_id AND pn.type = "name"
                AND pn.start_date <= member.left_house AND member.left_house <= pn.end_date
                AND house = :house AND constituency = :cons
                AND member.person_id != :pid AND entered_house ' . $direction . ' :date ORDER BY entered_house ' . $order,
            array(
                ':house' => HOUSE_TYPE_COMMONS,
                ':cons' => $this->constituency(),
                ':pid' => $this->person_id(),
                ':date' => $entered_house['date'],
            ));
        $mships = array(); $last_pid = null;
        for ($r = 0; $r < $q->rows(); $r++) {
            $pid = $q->field($r, 'person_id');
            $name = $q->field($r, 'given_name') . ' ' . $q->field($r, 'family_name');
            if ($last_pid != $pid) {
                $mships[] = array(
                    'href' => WEBPATH . 'mp/?pid='.$pid,
                    'text' => $name
                );
                $last_pid = $pid;
            }
        }
        return $mships;
    }

    public function previous_mps() {
        return $this->_previous_future_mps_query('<');
    }

    public function future_mps() {
        return $this->_previous_future_mps_query('>');
    }

    public function current_member_anywhere() {
        $is_current = false;
        $current_memberships = $this->current_member();
        foreach ($current_memberships as $current_memberships) {
            if ($current_memberships === true) {
                $is_current = true;
            }
        }

        return $is_current;
    }
}
