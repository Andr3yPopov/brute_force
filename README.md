simple_brute_force.py - самый простой брутфорс, где пароли перебираются один за другим\n
multiprocessing_brute_force.py - запускает нексолько параллельных процессов для перебора паролей
generate_passwords.py - генерирует пароли на основе имени, фамилии и ника и сохраняет их в словарь, который можно использовать с двумя предыдущими программами для перебора (я использовал с simple_brute_force.py)
with_csrf.php - версия сайта, защищенная CSRF-токеном
with_ip_blocking.php - версия сайта, которая блокирует ip адреса, с которых поступает много неудачных запросов
with_capcha.php - версия сайта с капчей, где надо посчитать количество линий на изображении
