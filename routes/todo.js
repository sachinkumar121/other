var express = require('express')
var router = express.Router()
const logged_in = true;
// Middleware function to log the request time
router.use(function timeLog (req, res, next) {
  console.log('Time: ', Date.now())
  next()
})

// Get To Do list
router.get('/',function(req, res) {
	res.send("List of all to-dos")
})

router.get('/:todo_id(\[0-9]+\)', function(req, res, next){
	console.log("Is user Logged in: ", logged_in)
	next()
}, function(req, res){
	res.send(req.params)
})
module.exports = router