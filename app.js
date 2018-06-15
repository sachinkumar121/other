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
