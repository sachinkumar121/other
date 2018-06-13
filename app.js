var express = require('express');
var path = require('path');
var favicon = require('serve-favicon');
var logger = require('morgan');
var cookieParser = require('cookie-parser');
var bodyParser = require('body-parser');
var responseTime = require('response-time')

var index = require('./routes/index');
var users = require('./routes/users');
var todos = require('./routes/todo');

var app = express();

// view engine setup
app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'pug');

// uncomment after placing your favicon in /public
//app.use(favicon(path.join(__dirname, 'public', 'favicon.ico')));
app.use(logger('dev'));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: false }));
app.use(cookieParser());
app.use(responseTime());
app.use(express.static(path.join(__dirname, 'public')));

app.use('/', index);
app.use('/users', users);
app.use('/todo',todos);
// Basic Routing
app.get('/login', (req, res, next) => {
	console.log(req.url);
	res.send("Login Form");
})
/*app.use((req, res) => {
	res.setHeader('Content-Type', 'text/plain')
	res.write("You Posted")
	res.end(JSON.stringify(req.body. null, 2))
})*/
app.get('/a_route_behind_paywall',
  function checkIfPaidSubscriber (req, res, next) {
    if (true) {
      // continue handling this request
	  console.log("route");
      next('route')
    }
    else{
	  console.log("else");
      next();
    }
  }, function getPaidContent (req, res, next) {
	   console.log("getPaidContent");
	   res.send("getPaidContent");
  }, function(req, res){
  		res.send("final")
  })

// catch 404 and forward to error handler
app.use(function(req, res, next) {
  var err = new Error('Not Found');
  err.status = 404;
  next(err);
});

// error handler
app.use(function(err, req, res, next) {
  // set locals, only providing error in development
  res.locals.message = err.message;
  res.locals.error = req.app.get('env') === 'development' ? err : {};

  // render the error page
  res.status(err.status || 500);
  res.render('error');
});
module.exports = app;
http://192.168.0.1:8090/
username - sachin.kumar	
pass - Welcome@1

Upwork Details
U : softobiz_149
P : 85h#Bs9!

Beanstalk

URl - https://softobiz.beanstalkapp.com/invitations/8207412795380aab5a8e1a57e8/finish	
sachinkumar
S@ch!n@_1k3u5m7

Website credentials
xyztech@gmail.com
admin123

HTML URL:- http://192.168.0.105:8217/#/view_employees

pluralsight
U : softobiz
P : 81R6EoRa%

====Email address===
sachin.kumar@softobiz.net

====Team
https://softobiz.teamworkpm.net
pass- Welcome@1

ravi.k@softobiz.com
ravimbt007@

==========softobiztechnologies.peoplehr.net====
mata786

============visualstudio==========
softobiztechnologies.visualstudio.com
sachin.kumar@softobiz.net
sachin1

===Gmail=====
https://docs.google.com/presentation/u/1/
U : softobiz.provider@gmail.com
P : Unitiled@sofobiz#1489

====Visual Studio===
U : ravi.k@softobiz.com
P : Tulo2253

set NODE_OPTIONS="--max-old-space-size=8192
==== Build Process ===
(1) check if AOT folder doesn't exists then run the following command and in (main ts) app module pointed from app.module
	node_modules/.bin/ngc -p tsconfig-aot.json
	give permissions to aot and dist folder.
	
(2) if aot is already there then change main.ts uncomment "aot/app/app.module.ngfactory" portion. and run following command.	

	node_modules/.bin/ngc -p tsconfig-aot.json
	node --max-old-space-size=8192 node_modules/.bin/ngc -p tsconfig-aot.json(if js out_of_heap_memory error occurs)

(3) If any error exists then remove the error first and change main.ts to app.module. Re-run the project. 
After successfully running change main.ts to "aot/app/app.module.ngfactory" and repeat step 2.

(4) Finally run following command
	node_modules/.bin/rollup -c rollup-config.js
	node --max-old-space-size=8192 node_modules/.bin/rollup -c rollup-config.js
(5) Change Index.html
	add comment over 
		"<script src="systemjs.config.js"></script>
    	<script>
    	System.import('app').catch(function(err){ console.error(err); });
    	</script>"
    Remove comment over
    	"<script src="dist/build.js"></script>"		
(6) Add code now.
	git add aot/ dist/	
	commit the code
	and push code to origin.
	(a) 
		1. If the above ask you to pull first then pull and check conflict files.
		remove conflict if any.
		2. create build again and push code to origin.
	(b) if no conflict it is time to say good to go.		





Access -
echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf && sudo sysctl -p





	==============SQL changes===========
	INSERT INTO `vendortrax`.`roles` (`id`, `role`, `role_type`, `created_at`, `updated_at`) VALUES (NULL, 'property', 'property', NULL, NULL), (NULL, 'contact', 'contact', NULL, NULL);

	===emails===
	'company','pm_company','user','customer','property_manager','billing','assistant','personnel','pm_user'
	'company','pm_company','user','customer','property_manager','billing','assistant','personnel','pm_user', 'contact', 'property'

	====phones===
	'company','pm_company','user','customer','property_manager','billing','assistant','personnel','pm_user'
	'company','pm_company','user','customer','property_manager','billing','assistant','personnel','pm_user','contact', 'property', 'employee'

	====notes====
	'users','customer','property_manager','billing','assistant','customer','pm_company','personnel','pm_user'
	'users','customer','property_manager','billing','assistant','customer','pm_company','personnel','pm_user','contact', 'property'


ALTER TABLE  `users` ADD  `is_restricted` ENUM(  '0',  '1' ) NOT NULL DEFAULT  '0' AFTER  `status` ;
dob in users change to varchar.


ALTER TABLE `users` ADD `is_restricted` TINYINT(1) NOT NULL DEFAULT '0' AFTER `status`;

ALTER TABLE  `files` ADD  `upload_by` ENUM(  'client',  'user' ) NULL DEFAULT  'user' AFTER  `url`

INSERT INTO `activity_keys` (`id`, `name`, `created_at`, `updated_at`) VALUES (NULL, 'client_upload', NULL, NULL);


===================Live===============
PHP Myadmin - https://www.vendortrax.com/rdsmysec 
host: vendortrax.cgtcj6x81wqj.us-east-1.rds.amazonaws.com 
User: vendordev 
Pass: MulqhJiOTWt7YFl3 
database name : vendortrax_dev
website url : staging.vendortrax.com
api url: staging-api.vendortrax.com

Rds Details as per below:-
https://www.vendortrax.com/mysecEn
Host: vendortrax-encrypted.cgtcj6x81wqj.us-east-1.rds.amazonaws.com
User: vendordev  
Pass: MulqhJiOTWt7YFl3

http://myadm.vendortrax.com 
User Base Auth 
User: dev 
Pass: qRBFgvGz 
DB details 
	User: vendordev  
	Pass: MulqhJiOTWt7YFl3 


Check log-http://api.vendortrax.com/storage/logs/laravel.log
"test@vendortrax.com" 

password in "admin123" 

===============Staging=============
PHPamyadmin - http://192.168.0.14/phpmyadmin/
Site url - http://vendortraxlive.softobiz.biz
username - testing@vendortrax.com
password - admin123

echo 3 > /proc/sys/vm/drop_caches
