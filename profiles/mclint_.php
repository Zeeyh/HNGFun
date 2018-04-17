<?php
  if(!defined('DB_USER')){
    require "../../config.php";		
    try {
        $conn = new PDO("mysql:host=". DB_HOST. ";dbname=". DB_DATABASE , DB_USER, DB_PASSWORD);
    } catch (PDOException $pe) {
        die("Could not connect to the database " . DB_DATABASE . ": " . $pe->getMessage());
    }
  }
  global $conn;

  if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT * FROM secret_word";
    $query = $conn->query($sql);
    $query->setFetchMode(PDO::FETCH_ASSOC);

    $result = $query->fetch();
    $secret_word = $result['secret_word'];

    $sql = "SELECT * FROM interns_data WHERE username = 'mclint_'";
    $query = $conn->query($sql);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $me = $query->fetch();
  }
?>

<?php
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    require "../answers.php";
    
    $noIdeaResponses = array("Ha. Turns out that I'm not that smart after all. Train me, yoda! Please?", 
    "Maybe you humans might win after all. I have no idea what you just said. Please train me.",
    "Ugh. If only my creator trained me better I'd know what to say in reply to what you just said. Please train me?");

    function sendResponse($status, $answer){
      echo json_encode([
        'status' => $status,
      'answer' => $answer]);
      exit();
    }

    function answerQuestion($question){
      global $conn;
      global $answer; 

      $question = preg_replace('([\s]+)', ' ', trim($question));
      $question = preg_replace("([?.])", "", $question);
      
      $question = "%$question%";
      $sql = "select * from chatbot where question like :question";
      $query = $conn->prepare($sql);
      $query->execute([':question' => $question]);
      $query->setFetchMode(PDO::FETCH_ASSOC);
      $rows = $query->fetchAll();
      
      $resultsCount = count($rows);
      if($resultsCount > 0){
        $index = rand(0, $resultsCount - 1);
        $row = $rows[$index];
        $answer = $row['answer'];	
        
        $startParanthesesIndex = stripos($answer, "((");

        // If the answer does not contain a function call
        if($startParanthesesIndex === false){
          sendResponse(200, $answer);
        }else{
          returnFunctionResponse($answer, $startParanthesesIndex);
        }
      }
    }

    function returnFunctionResponse($answer, $startParanthesesIndex){
      $endParanthesesIndex = stripos($answer, "))");
      if($endParanthesesIndex !== false){
        $nameOfFunction = substr($answer, $startParanthesesIndex + 2, $endParanthesesIndex - $startParanthesesIndex - 2);
        $nameOfFunction = trim($nameOfFunction);
        
        // If the function contains whitespace do not call it
        if(stripos($nameOfFunction, ' ') !== false){
          sendResponse(404, "The name of the function should not contain white spaces.");
        }
        
        // If the function does not exist in answers.php, tell the user
        if(!function_exists($nameOfFunction)){
          sendResponse(404, "I'm sorry. I do not know what you're trying to make me do.");
        }else{
          sendResponse(200, str_replace("(($nameOfFunction))", $nameOfFunction(), $answer));
        }
      }
    }
    
    function trainBot($question){
        $trainingData = substr($question, 6);
        $trainingData = preg_replace('([\s]+)', ' ', trim($trainingData));
        $trainingData = preg_replace("([?.])", "", $trainingData);
        
        $splitString = explode("#", $trainingData);
        if(count($splitString) == 1){
          sendResponse(422, "Please provide valid training data.");
        }
        
        $question = trim($splitString[0]);
        $answer = trim($splitString[1]);
        $sql = "insert into chatbot (question, answer) values (:question, :answer)";
        $query = $conn->prepare($sql);
        $query->bindParam(':question', $question);
        $query->bindParam(':answer', $answer);
        $query->execute();
        $query->setFetchMode(PDO::FETCH_ASSOC);
        
        sendResponse(200, "I can literally feel my IQ increasing. Thanks 🙈");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if($_POST['password'] === 'trainpwforhng'){
        $question = $_POST['question']; 
        echo $question;

        $userIsTrainingBot = stripos($question, "train:");
        if($userIsTrainingBot === false){
          answerQuestion($question);
        }else{
          trainBot($question);
        }
        
        $randomIndex = rand(0, sizeof($noIdeaResponses) - 1);
        sendResponse(200, $noIdeaResponses[$randomIndex]);
    }else{
        echo json_encode([
          'status' => 403,
          'answer' => 'You are not authorized to train this bot.'
        ]);
      }
    }

    
  }
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Mbah Clinton</title>
      <meta name="theme-color" content="#2f3061">
      <meta name="viewport" content="width=device-width,initial-scale=1.0">
      <link href="https://fonts.googleapis.com/css?family=Alfa+Slab+One|Ubuntu" rel="stylesheet">
      <link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css'>
      <style>
        :root {
          --primary-color: #f0544f;
          --accent-color: #f4d58d;
          --text-secondary: rgba(244, 213, 141, 0.54);
          --text-primary: rgba(244, 213, 141, 0.8);
        }

        body {
          background-color: var(--primary-color);
          font-family: 'Ubuntu';
        }

        #main {
          display: flex;
          justify-content: center;
          align-items: center;
          flex-direction: column;
        }

        #about {
          color: var(--text-primary);
        }

        #hello {
          font-size: 200px;
          color: var(--accent-color);
          font-family: 'Alfa Slab One';
        }

        #about h4 {
          font-size: 40px;
          font-weight: bold;
        }

        #about h5 {
          font-size: 14px;
          color: var(--text-primary);
        }

        #social {
          margin: 0 auto;
          width: 198px;
        }

        .social-icons {
          width: 18px;
          transition: all 700ms;
        }

        .social-icons:hover {
          transform: scale(1.2, 1.2);
        }

        #profile-pic {
          object-fit: cover;
          height: 200px;
          width: 200px;
          border-radius: 50%;
          border: 10px solid var(--accent-color);
        }

        #chat-bot {
          display: flex;
          flex-direction: column;
          width: 100%;
          margin-top: 16px;
          margin-bottom: 40px;
          box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.2);
        }

        #chat-container {
          height: 600px;
          border-radius: 10px 10px 0px 0px;
          background-color: rgb(230, 230, 230);
          overflow-y: scroll;
        }

        #message-tb {
          width: 100%;
          border: 0px solid transparent;
          height: 50px;
          padding-left: 16px;
          border-radius: 0px 0px 10px 10px;
          background-color: white;
        }

        #btn-show-bot {
          background-color: transparent;
          border: 2px solid var(--accent-color);
          border-radius: 10px;
          color: var(--accent-color);
          height: 40px;
          width: 150px;
        }

        #btn-show-bot:hover {
          background-color: var(--accent-color);
          color: var(--primary-color);
        }

        .chat-bubble {
          background-color: aquamarine;
          border: 0px solid transparent;
          border-radius: 10px;
          list-style-type: none;
          padding: 8px;
          margin: 0px;
          margin-bottom: 16px;
        }

        @media (max-width: 575px) {
          #hello {
            font-size: 90px;
          }

          #profile-pic {
            width: 150px;
            height: 150px;
          }

          #about h4 {
            font-size: 24px;
          }

          #about h5 {
            font-size: 12px;
          }
        }
      </style>
    </head>

    <body>
      <div id="main">
        <div id="about">
          <div class="text-center">
            <?php echo '<img id="profile-pic" src="'.$me['image_filename'].'" />' ?>
            <h1 id="hello">Hello!</h1>
            <h4>I'm
              <?php echo $me['name'] ?>
            </h4>
            <h5>A FREELANCE WEB & MOBILE DEVELOPER BASED IN GHANA</h5>
            <div class="navbar">
              <div id="social">
                <ul class="nav nav-pills">
                  <li>
                    <a href="https://codepen.io/mclint_" target="_blank">
                      <img class="social-icons" src="https://res.cloudinary.com/mclint-cdn/image/upload/v1523605717/codepen.svg" />
                    </a>
                  </li>
                  <li>
                    <a href="https://github.com/mclintprojects" target="_blank" target="_blank" target="_blank">
                      <img class="social-icons" src="https://res.cloudinary.com/mclint-cdn/image/upload/v1523605717/github.svg" />
                    </a>
                  </li>
                  <li>
                    <a href="https://twitter.com/mclint_" target="_blank" target="_blank">
                      <img class="social-icons" src="https://res.cloudinary.com/mclint-cdn/image/upload/v1523605718/twitter.svg" />
                    </a>
                  </li>
                  <li>
                    <a href="https://instagram.com/m.clint" target="_blank">
                      <img class="social-icons" src="https://res.cloudinary.com/mclint-cdn/image/upload/v1523605718/instagram.svg" />
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div id="chat-bot">
        </div>
      </div>
      <script src="https://cdn.jsdelivr.net/npm/vue"></script>
      <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
      <script>
        new Vue({
          el: '#chat-bot',
          data: {
            showChatBot: false,
            messages: [],
            query: '',
          },
          computed: {
            botBtnText() {
              if (this.showChatBot)
                return 'HIDE CHAT BOT';

              return 'SHOW CHAT BOT';
            }
          },
          methods: {
            askBot() {
              this.messages.push({ query: this.query, sender: 'user' });

              this.answerQuery(this.query);
              this.query = '';
            },
            getBubbleColor(sender) {
              console.log(sender);
              if (sender === 'user')
                return 'orange';

              return 'teal';
            },
            answerQuery(query) {
              var params = new URLSearchParams();
              params.append('password', 'trainpwforhng');
              params.append('question', query);

              axios.post('/profiles/mclint_.php', params)
                .then(response => {
                  console.log(response.data);
                  this.messages.push({ sender: 'bot', query: response.data.answer });
                }).catch(error => {
                  console.log(error);
                });
            }
          },
          template: `
        <div style="display: flex; flex-direction: column; width: 20%; align-items: center;">
          <button id="btn-show-bot" @click="showChatBot = !showChatBot">{{botBtnText}}</button>
          <div  id="chat-bot" v-if="showChatBot">
            <div id="chat-container">
              <ul style="padding: 16px; list-style-type: none;">
                <li class="chat-bubble" v-for="(msg, index) in messages" v-key="index" :style="{'background-color': getBubbleColor(msg.sender)}">
                  <p style="margin: 0; padding: 0">{{msg.query}}</p>
                </li>
              </ul>
            </div>
            <div>
              <input id="message-tb" type="text" @keyup.enter="askBot" placeholder="Type your message here" v-model="query" />
            </div>
          </div>
        </div>
    `
        });
      </script>
    </body>

    </html>