    <div class="<?= $current_assembly ?>">
        <div class="person-header <?= $this_page ?>">
            <div class=" full-page__row">
            <div class="person-header__content page-content__row">
                <div class="person-name">
                    <h1>
                        <?php if ( $image ) { ?>
                        <span class="mp-image">
                        <img src="<?= $image ?>" height="48">
                        </span>
                        <?php } ?>
                        <?= $full_name ?>
                    </h1>
                </div>
                <div class="person-constituency">
                     <?php if ( $constituency && $this_page != 'peer' && $this_page != 'royal' ) { ?><span class="constituency"><?= $constituency ?></span> <?php } ?><span class="party <?= $party_short ?>"><?= $party ?></span>
                </div>
                <div class="person-search">
                    <form action="<?= $search_url ?>" method="get" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Person'); return false;">
                        <input id="person_search_input" name="q" maxlength="200" placeholder="Search this person's speeches"><input type="submit" class="submit" value="GO">
                        <input type="hidden" name="pid" value="<?= $person_id ?>">
                    </form>
                </div>
                <div class="person-buttons">
                    <?php if ($current_member_anywhere): ?>
                    <a href="https://www.writetothem.com/<?php
                        if ($current_member[HOUSE_TYPE_LORDS]) {
                            echo "?person=uk.org.publicwhip/person/$person_id";
                        }
                        if ($the_users_mp) {
                            echo "?a=WMC&amp;pc=" . htmlentities(urlencode($user_postcode));
                        }
                    ?>" class="button wtt" onclick="trackLinkClick(this, 'Links', 'WriteToThem', 'Person'); return false;"><img src="/style/img/envelope.png">Send a message</a>

                    <?php endif; ?>
                    <?php if ($has_email_alerts): ?>
                    <a href="<?= WEBPATH ?>alert/?pid=<?= $person_id ?>#" class="button alert" onclick="trackLinkClick(this, 'Alert', 'Search', 'Person'); return false;"><img src="/style/img/plus-circle.png">Get email updates</a>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>
    </div>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <?php if ($has_voting_record): ?>
            <div class="person-navigation page-content__row">
                <ul>
                    <li class="active"><a href="<?= $member_url ?>">Overview</a></li>
                    <li><a href="<?= $member_url ?>/votes">Voting Record</a></li>
                </ul>
            </div>
            <?php endif; ?>
            <div class="person-panels page-content__row">
                <div class="sidebar__unit in-page-nav">
                    <ul data-magellan-expedition="fixed">
                      <?php if ($has_voting_record): ?>
                        <li data-magellan-arrival="votes"><a href="#votes">Votes</a></li>
                      <?php endif; ?>
                      <?php if ($has_recent_appearances): ?>
                        <li data-magellan-arrival="appearances"><a href="#appearances">Appearances</a></li>
                      <?php endif; ?>
                        <li data-magellan-arrival="profile"><a href="#profile">Profile</a></li>
                        <li data-magellan-arrival="numerology"><a href="#numerology">Numerology</a></li>
                      <?php if ($register_interests): ?>
                        <li data-magellan-arrival="register"><a href="#register">Register of Interests</a></li>
                      <?php endif; ?>
                    </ul>
                    <div>&nbsp;</div>
                </div>
                <div class="primary-content__unit">

                    <?php if ($party == 'Sinn Fein' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                    <div class="panel">
                        <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_voting_record): ?>
                    <div class="panel">
                        <a name="votes"></a>
                        <h2 data-magellan-destination="votes">Voting Summary <small><a href="<?= $member_url ?>/votes">More Here</a></small></h2>

                        <h3>How <?= $full_name ?> voted<?= isset($key_votes['since_string']) ? $key_votes['since_string'] : '' ?><br>
                        <small>Randomly generated selection of topics. <a href="<?= $member_url ?>/votes">See full list</a>.</small></h3>

                        <?php if (count($key_votes['key_votes']) > 0): ?>

                            <ul class="policies">

                            <?php foreach ($key_votes['key_votes'] as $key_vote): ?>

                            <li><?= $key_vote['desc'] ?><a class="dream_details" href="http://www.publicwhip.org.uk/mp.php?mpid=<?= $member_id ?>&dmp=<?= $key_vote['policy_id'] ?>">Details</a></li>

                            <?php endforeach; ?>

                            </ul>

                            <p>See our much more detailed, easier-to-read <a href="<?= $member_url ?>/votes">analysis of votes</a> on <a href="<?= $member_url ?>/votes#health">health</a>, <a href="<?= $member_url ?>/votes#welfare">welfare</a>, <a href="<?= $member_url ?>/votes#foreign">foreign policy</a>, <a href="<?= $member_url ?>/votes#social">social issues</a>, <a href="<?= $member_url ?>/votes#taxation">taxation</a> and more.</p>

                        <?php else: ?>

                            <p>No votes to display.</p>

                        <?php endif; ?>

                        <p><?= $full_name ?> <?= $rebellion_rate ?></p>

                    </div>
                    <?php endif; ?>

                    <?php if ($has_recent_appearances): ?>
                    <div class="panel">
                        <a name="appearances"></a>
                        <h2 data-magellan-destination="appearances">Recent appearances</h2>

                        <?php if (count($recent_appearances) > 0): ?>

                            <ul class="appearances">

                            <?php foreach ($recent_appearances['appearances'] as $recent_appearance): ?>

                                <li>
                                    <h4><a href="<?= $recent_appearance['listurl'] ?>"><?= $recent_appearance['parent']['body'] ?></a> <span class="date"><?= date('j M Y', strtotime($recent_appearance['hdate'])) ?></span></h4>
                                    <blockquote><?= $recent_appearance['extract'] ?></blockquote>
                                </li>

                            <?php endforeach; ?>

                            </ul>

                            <p><a href="<?= $recent_appearances['more_href'] ?>"><?= $recent_appearances['more_text'] ?></a></p>

                            <?php if (isset($recent_appearances['additional_links'])): ?>
                            <?= $recent_appearances['additional_links'] ?>
                            <?php endif; ?>

                        <?php else: ?>

                            <p>No recent appearances to display.</p>

                        <?php endif; ?>

                    </div>
                    <?php endif; ?>

                    <div class="panel">
                        <a name="profile"></a>
                        <h2 data-magellan-destination="profile">Profile</h2>

                        <p><?= $member_summary ?></p>

                        <?php if (count($useful_links) > 0): ?>

                        <ul class="comma-list">

                            <?php foreach ($useful_links as $link): ?>
                            <li><a href="<?= $link['href'] ?>"><?= $link['text'] ?></a></li>
                            <?php endforeach; ?>

                        </ul>

                        <?php endif; ?>

                        <?php if ($has_expenses): ?>
                        <h3>Expenses</h3>

                        <p class="quiet">Expenses data for MPs is available from 2004 onwards
split over several locations. At the moment we don't have the time to convert
it to a format we can display on the site so we just have to point you to where
you can find it.</p>

                        <ul>
                            <li><a href="<?= $expenses_url_2004 ?>">Expenses from 2004 to 2009</a></li>
                            <li><a href="http://www.parliamentary-standards.org.uk/AnnualisedData.aspx">Expenses from 2010 onwards</a></li>
                        </ul>
                        <?php endif; ?>

                        <?php if (count($topics_of_interest) > 0): ?>

                        <h3>Topics of interest</h3>

                        <ul class="comma-list">

                            <?php foreach ($topics_of_interest as $topic): ?>
                            <li><?= $topic ?></li>
                            <?php endforeach; ?>

                        </ul>

                        <?php endif; ?>

                        <?php if (count($previous_offices) > 0): ?>

                        <h3>Other offices held in the past</h3>

                        <ul>

                            <?php foreach ($previous_offices as $office): ?>
                            <li><?= $office ?></li>
                            <?php endforeach; ?>

                        </ul>

                        <?php endif; ?>

                        <?php if (count($constituency_previous_mps) > 0): ?>

                        <h3>Previous MPs in this constituency</h3>

                        <ul class="comma-list">

                            <?php foreach ($constituency_previous_mps as $constituency_previous_mp): ?>
                            <li><a href="<?= $constituency_previous_mp['href'] ?>"><?= $constituency_previous_mp['text'] ?></a></li>
                            <?php endforeach; ?>

                        </ul>

                        <?php endif; ?>

                        <?php if (count($constituency_future_mps) > 0): ?>

                        <h3>Future MPs in this constituency</h3>

                        <ul class="comma-list">

                            <?php foreach ($constituency_future_mps as $constituency_future_mp): ?>
                            <li><a href="<?= $constituency_future_mp['href'] ?>"><?= $constituency_future_mp['text'] ?></a></li>
                            <?php endforeach; ?>

                        </ul>

                        <?php endif; ?>

                        <?php if (count($public_bill_committees) > 0): ?>

                        <h3>Public bill committees <small>(Sittings attended)</small></h3>

                        <ul>

                            <?php foreach ($public_bill_committees as $committee): ?>
                            <li><a href="<?= $committee['href'] ?>"><?= $committee['text'] ?></a> (<?= $committee['attending'] ?>)</li>
                            <?php endforeach; ?>

                        </ul>

                        <?php endif; ?>

                    </div>

                    <div class="panel">
                        <a name="numerology"></a>
                        <h2 data-magellan-destination="numerology">Numerology</h2>

                        <?php if (count($numerology) > 0): ?>

                        <p>Please note that numbers do not measure quality. Also, representatives may do other things not currently covered by this site.<br><small><a href="<?= WEBPATH ?>help/#numbers">More about this</a></small></p>

                        <ul class="numerology">

                            <?php foreach ($numerology as $numerology_item): ?>
                            <?php if ($numerology_item): ?>
                            <li><?= $numerology_item ?></li>
                            <?php endif; ?>
                            <?php endforeach; ?>

                        </ul>

                        <?php else: ?>

                        <p>No information to display yet.</p>

                        <?php endif; ?>

                    </div>

                    <?php if ($register_interests): ?>
                    <div class="panel register">
                        <a name="register"></a>
                        <h2 data-magellan-destination="register">Register of Members&rsquo; Interests</h2>

                        <?php if ($register_interests['date']): ?>
                            <p>Last updated: <?= $register_interests['date'] ?>.</p>
                        <?php endif; ?>

                        <?= $register_interests['data'] ?>

                        <p>
                            <a href="<?= WEBPATH ?>regmem/?p=<?= $person_id ?>">View the history of this MP&rsquo;s entries in the Register</a>
                        </p>

                        <p>
                             <a class="moreinfo-link" href="http://www.publications.parliament.uk/pa/cm/cmregmem/100927/introduction.htm">More about the register</a>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="panel">
                        <p class="quiet">Please feel free to use the information on this page, but if
                            you do you must cite TheyWorkForYou.com in the body
                            of your articles as the source.</p>

                        <p class="quiet">Information on this page is compiled from
                            <a href="http://www.publicwhip.org.uk/">The Public Whip</a>, <a href="http://www.parliament.uk/">UK Parliament</a>, <a href="http://www.scottish.parliament.uk/">Scottish Parliament</a> and <a href="http://www.niassembly.gov.uk/">Northern Ireland Assembly</a>. Where possible information is regularly updated from these sources on either a daily or weekly basis, but may be subject to short delays in processing.</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>