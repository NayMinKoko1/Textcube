<?php
/// Copyright (c) 2004-2009, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)


class Model_OpenSearchProvider extends XMLTree {
	
	/// Set the descriptors to DOM tree
	function setDescriptor ($name, $content, $type = null) {
		return $this->setValue('/OpenSearchDescription/'.$name, $content, $type);
	}
}
?>