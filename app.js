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
<div class="container">
  <div class="row">
    <div class="col-md-6 offset-md-3">
    <div class="example-container">
    <div class="card">
  <div class="card-header">
    Login
  </div>
  <div class="card-body">
	<form class="login-form">
	<div class="form-group">
	<mat-form-field class="example-full-width">
	<input matInput [(ngModel)]="useremail" name="useremail" placeholder="Enter your email" required >
	<!--         <mat-error *ngIf="true">Email is required.</mat-error>
	-->      </mat-form-field></div>
	<!-- <mat-form-field class="example-full-width">
	<input matInput [(ngModel)]="username" name="username" placeholder="Enter your email" required >
	<mat-error *ngIf="true">Username is required.</mat-error>
	</mat-form-field> -->
	<div class="form-group">
	<mat-form-field class="example-full-width">
	<input matInput placeholder="Enter your password" [(ngModel)]="password" type="password" name="password" required>
	</mat-form-field>
	</div>

	<div class="form-group">
	<div>
	<!-- <button type="submit" class="btn btn-primary">
	Login
	</button> -->

	<button mat-raised-button color="primary">Login</button>

	<a class="btn btn-link" href="">
	Forgot Your Password?
	</a>
	</div>
	</div>
	</form>
  </div>
</div>

    

    </div>
    </div>
  </div>
</div>

<!-- <mat-card class="example-card">
  <mat-card-header>
    <div mat-card-avatar class="example-header-image"></div>
    <mat-card-title>Shiba Inu</mat-card-title>
    <mat-card-subtitle>Dog Breed</mat-card-subtitle>
  </mat-card-header>
  <img mat-card-image src="https://material.angular.io/assets/img/examples/shiba2.jpg" alt="Photo of a Shiba Inu">
  <mat-card-content>
    <p>
      The Shiba Inu is the smallest of the six original and distinct spitz breeds of dog from Japan.
      A small, agile dog that copes very well with mountainous terrain, the Shiba Inu was originally
      bred for hunting.
    </p>
  </mat-card-content>
  <mat-card-actions>
    <button mat-button>LIKE</button>
    <button mat-button>SHARE</button>
  </mat-card-actions>
  
</mat-card> -->
<!-- <div class="container">
  <div class="row">
	<mat-card class="example-card">
	   <mat-card-header>
	     <mat-card-title>Login</mat-card-title>
	   </mat-card-header>
	   <mat-card-content>
	     <form class="example-form">
	       <table class="example-full-width" cellspacing="0">
	         <tr>
	           <td>
	             <mat-form-field class="example-full-width">
	             <input matInput placeholder="Username" [(ngModel)]="username" name="username" required>
	             </mat-form-field>
	           </td>
	         </tr>
	         <tr>
	         <td><mat-form-field class="example-full-width">
	           <input matInput placeholder="Password" [(ngModel)]="password"type="password" name="password" required>
	         </mat-form-field></td>
	       </tr></table>
	     </form>
	     <mat-spinner [style.display]="showSpinner ? 'block' : 'none'"></mat-spinner>
	   </mat-card-content>
	   <mat-card-actions>
	     <button mat-raised-button (click)="login()" color="primary">Login</button>
	   </mat-card-actions>
	 </mat-card> 
  </div>  
</div> -->
.example-card {
  max-width: 400px;
  margin-top: 5%;
}

.example-header-image {
  background-image: url('https://material.angular.io/assets/img/examples/shiba1.jpg');
  background-size: cover;
}

.example-icon {
  padding: 0 14px;
}

.example-spacer {
  flex: 1 1 auto;
}

.example-container {
  display: flex;
  flex-direction: column;
  margin-top: 5px;
}

.example-container > * {
  width: 100%;
}

.example-form {
  min-width: 150px;
  max-width: 500px;
  width: 100%;
}

.example-full-width {
  width: 100%;
}
