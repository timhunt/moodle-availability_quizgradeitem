# Restriction by quiz part score #

This availability condition allows activities and resources to be made available depending on a student's marks in a section of quiz.

Teachers can create rules based on the percentage of marks achieved in a section, or grade item*, within a particular quiz. For example, an activity can be shown only to students who passed a particular section of a quiz, or alternatively only to students who have failed in a specific section.

The condition integrates with Moodle's standard availability API and appears alongside the other restrict access conditions.

## Note! ## 

"Grade item" here refers to grade items set in the Quiz activity. These grade items are not set or do not appear in the gradebook. To set up a grade item within a quiz, you:
1. Go to the Questions tab in the Quiz you wish to edit.
1. Select Grade items setup from the dropdown menu.
1. Click Add grade item and rename if needed.
1. Select the correct Grade item for all questions you want to add to a grade item.



## To install ##

Once this is published, you will be able to install it from
Link will follow

Alternatively you can install using git. Run these commands in the root of your
Moodle site:

    git clone https://github.com/timhunt/moodle-availability_quizgradeitem.git public/availability/condition/quizgradeitem
    echo '/public/availability/condition/quizgradeitem/' >> .git/info/exclude

Then visit Admin -> Notifications to complete the installation.


## To use ##

1. Turn editing on in your course.
1. Edit the activity or resource that you want to restrict.
1. In the Restrict access section, select Add restriction....
1. Choose Quiz grade.
1. Select the quiz to use for the condition.
1. Select the section, 
1. Configure the grade requirement.
1. Save the activity settings.

Students will see or gain access to the activity according to the configured rule and their current grade in the selected quiz.

## Example use case ##

This plugin can be used to implement learning pathways such as:

1. Unlocking advanced materials only after achieving a passing grade.
1. Providing additional activities for students who scored below a threshold.
1. Releasing certificates or badges after successfully completing an assessment.
1. Creating adaptive learning sequences based on assessment performance.

## Credits ##

This plugin was created by Tim Hunt, Andreas Steiger Dustin Schiele, Christine Lent and Anna Kamula at #MootDACH 2026 Dev Camp.


## License ##

2026 Tim Hunt, Andreas Steiger and Dustin Schiele

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
