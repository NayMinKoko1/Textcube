<?php
/// Copyright (c) 2004-2009, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

global $__gUserInfo;

// NOTICE : THIS MODEL WILL BE DEPRECATED FROM TEXTCUBE 1.6.1. USE User COMPONENT INSTEAD.
function getUserEmail($userid) {
	return Model_User::getEmail($userid);
}

function getUserIdByEmail($email) {
	return Model_User::getUserIdByEmail($email);
}

?>
