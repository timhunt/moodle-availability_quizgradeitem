# Bulk Export of Questions to Moodle XML #

This feature adds a new bulk action to the Question bank, allowing teachers and managers to export multiple selected questions directly in Moodle XML format.

Previously, exporting questions required navigating to the export interface and manually choosing categories or question sets. With this feature, users can simply select questions in the Question bank and export only those specific questions with a single action.


## To install (needed: the correct links, will follow)##

Once this is published, you will be able to install it from
Link will follow

Alternatively you can install using git. Run these commands in the root of your
Moodle site:

    git clone https://github.com/timhunt/moodle-availability_quizgradeitem.git public/availability/condition/quizgradeitem
    echo '/public/availability/condition/quizgradeitem/' >> .git/info/exclude

Then visit Admin -> Notifications to complete the installation.


## To use ##

1. Go to Question bank.
1. Select one or more questions using the checkboxes.
1. From the Bulk actions menu, choose Export selected questions as Moodle XML.
1. The selected questions will be exported and downloaded as a standard Moodle XML file.



## Credits ##

This plugin was created by Tim Hunt, Andreas Steiger and Dustin Schiele, (assisted by Christine Lent) at #MootDACH 2026 Dev Camp.


## License ##

2020 Tim Hunt, Andreas Steiger and Dustin Schiele

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
