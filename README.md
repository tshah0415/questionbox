## Question Box
What is it? It's a way for team members to ask channel-specific questions, anonymously or not (up to the asker) in a channel.
Then, other people can view those questions and vote on them. Whoever opened the Question Box, the admininstrator, can choose
to delete questions, publish the full list of questions with their vote counts, or clear out the questions and start over. Fun!

## Setup in 5 Steps

1. Clone this repo and put it somewhere! Heroku or AWS or what-have-you.
2. Set up a database: the provided [db schema](schema.txt) is what the app expects to see.
3. Create a `conf.php` file in the likeness of the [example](src/conf-example.php) that has your own credentials in it.
4. Now hopefully you can go to `https://www.your-wobsite.com/questionbox/index.html` (or however you set it up) and it has a button! **Click it!**
5. After choosing the team you desire, you should now have the app authed on your team. Check it out in the [App directory](https://my.slack.com/apps/A327R16JV-question-box) to confirm.

Yay! Type `/questionbox help` in Slack to see it spring to life!
