import mysql.connector

connection = mysql.connector.connect(
    host='testinghost',
    user='test_user',
    password='testMe',
    database='Testdata'
)

cursor = connection.cursor()

# Example to insert data
cursor.execute("INSERT INTO users (username, email, password) VALUES (%s, %s, %s)",
               ("Tester", "Test@test.com", 22))
connection.commit()

cursor.close()
connection.close()

