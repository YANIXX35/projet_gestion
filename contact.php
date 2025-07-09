<?php
// contact.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contact - DocGestion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #007BFF;
            color: white;
            padding: 20px;
            text-align: center;
        }
        main {
            padding: 30px;
        }
        section {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            max-width: 800px;
            margin: auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        form {
            margin-top: 20px;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #007BFF;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <header>
        <h1>Contactez-nous</h1>
    </header>
    <main>
        <section>
            <p>📍 Abidjan, Côte d'Ivoire</p>
            <p>📞 Téléphone : +225 01 23 45 67 89</p>
            <p>✉️ Email : support@docsystem.com</p>

            <form action="#" method="post">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" required>

                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required>

                <label for="message">Message :</label>
                <textarea id="message" name="message" rows="5" required></textarea>

                <button type="submit">Envoyer</button>
            </form>
        </section>
    </main>
</body>
</html>
