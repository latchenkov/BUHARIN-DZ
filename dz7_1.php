<?php

//включение отображения всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

//передача информации о типе содержимого и кодировке
header("Content-Type: text/html; charset=utf-8");

//Массив с используемыми городами
$cityes = array('Новосибирск', 'Москва', 'Ростов-на-Дону', 'Симферополь', 'Екатеринбург', 'Урюпинск', 'Санкт-Петербург', 'Грозный');

//Массив с используемыми категориями объявлений
$categoryes = array(
    'Транспорт' => array(1 => 'Автомобили', 2 => 'Велосипеды', 3 => 'Самокаты'),
    'Недвижимость' => array(4 => 'Квартиры', 5 => 'Дачи', 6 => 'Коттеджи'),
    'Работа' => array(7 => 'Менеджеры', 8 => 'Директоры', 9 => 'Получатели зарплаты')
);

//Функция фильтрации данных из полей
function filter_text($adv_array) {
//    Если не передано значение 'spam', то добавляем его
    if (!isset($adv_array['spam'])) {
        $adv_array['spam'] = '';
    }
    foreach ($adv_array as $key => $data) {
        $adv_array[$key] = strip_tags(trim($data)); //удаляет пробелы в начале и конце строки, а так же html-теги
        if ($key == 'price') {
            $adv_array[$key] = trim((float)$data); // если это поле 'price', то оставляем в нём только цифры
        }
    }
    return $adv_array;
}

//Если это первый запуск, то заносим в переменную значения вручную
//if(!isset($_COOKIE['advert'])){
//    setcookie('advert', '', time() + 3600*24*7, '/'); // устанавливаем куку
//    $temp = array(); // переменная для временного хранения данных из куки
//}  elseif($_COOKIE['advert'] != '') {
//    $temp = unserialize($_COOKIE['advert']); // иначе считываем куку в переменную
//}

// Нет нужды устанавливать пустую куку. Достаточно оставить только проверку существования
// и извлечь содержимое во временный массив. Как ниже.
if(isset($_COOKIE['advert'])){
    
    $temp = unserialize($_COOKIE['advert']); // иначе считываем куку в переменную
}
//Если не пусто значение категории
if ((isset($_POST['category'])) && ($_POST['category'] != '')) {

//    Если не передан id объявления, то добавляем объявление в массив
    if (!isset($_GET['id'])) {

//        А вот с этой непонятной конструкцией php не выдаёт предупреждение, что массив $_COOKIE пуст при первом добавлении объявления
//        (isset($_COOKIE['advert'])) ? $temp = unserialize($_COOKIE['advert']) : $temp = array();
// Эта непонятная конструкция вовсе не нужна
        
        $temp[] = filter_text($_POST); // заносим данные во временную переменную
       // setcookie('advert', serialize($temp), time() + 3600*24*7, '/'); // обновляем куку
    } else {
      //  $temp = unserialize($_COOKIE['advert']); // получаем данные из куки     // Эта строчка не нужна. Данные уже получены на 47 строке.
        $temp[$_GET['id']] = filter_text($_POST); // иначе обновляем объявление в массиве
      //  setcookie('advert', serialize($temp), time() + 3600*24*7, '/'); // обновляем куку
     //   header("Location: dz7_1.php"); // переадресация на страницу для очистки GET-параметров в адресной строке
    }
    // на 60 и 64 строке у тебя одинаковое действие. Вполне можно вынести за пределы if
    setcookie('advert', serialize($temp), time() + 3600*24*7, '/'); // обновляем куку
    // тоже можно вынести сюда. Будет очищать GET и POST параметры. И получать актуальный массив на 47 строке.
    header("Location: dz7_1.php"); // переадресация на страницу для очистки GET-параметров в адресной строке
    // после редиректа нужно поставить exit, иначе скрипт продолжит свое выполнение и могут быть не предсказуемые ошибки
    exit;
}

//Если передан айдишник объявления в массиве GET, то присваиваем значение переменной
if (isset($_GET['id'])) {
    $show_adv_id = $_GET['id'];
} else {
    $show_adv_id = '';
}

//Если передан id объявления для удаления, то удаляем объявление
if((!empty($_GET)) && (isset($_GET['del']))){
    $id_adv_delete = $_GET['del'];
    //$temp = unserialize($_COOKIE['advert']); // получаем данные из куки // опять. Эта переменная уже определена на 47 строке
    unset($temp[$id_adv_delete]);
    setcookie('advert', serialize($temp), time() + 3600*24*7, '/'); // обновляем куку
    header("Location: dz7_1.php"); // переадресация на страницу для очистки GET-параметров в адресной строке
// нужен exit. см. выше
    exit;
}

//Функция заполнения или получения данных объявления
function get_value($advs = '', $id_of_adv = '') {

    //    если не передан номер объявления или передан пустой массив с объявлениями, то в поля ничего не подставляем
    if (($id_of_adv == '') || (empty($advs))) {
        $value = array(
            'city' => '1',
            'category' => '',
            'type' => 'private',
            'name' => '',
            'email' => '',
            'spam' => '',
            'phone' => '',
            'advert_name' => '',
            'text' => '',
            'price' => '0'
        );
    } else {
//        а если передан, то заполняем поля данными объявления
        $value = $advs[$id_of_adv];
    }
    return $value;
}

$value = get_value($temp, $show_adv_id); //получаем данные объявления

//Функция построения списка городов
function get_cityes_list($list_of_cityes, $value) {

    $option_cityes = ''; //переменная для списка городов

    foreach ($list_of_cityes as $id => $city) {
//        Если номер города не пуст и равен переданному номеру города в функции, то отмечаем этот <option> как selected
        if (($value['city'] != '') && ($id == $value['city'])) {
            $city_checked = 'selected';
        } else {
            $city_checked = '';
        }
        $option_cityes .= '<option ' . $city_checked . ' value="' . $id . '" >' . $city . '</option>' . "\n";
    }
    echo $option_cityes; // вывод списка городов
}

//Функция построения списка категорий
function get_cat_list($list_of_cats, $value) {

    $option_categoryes = ''; //переменная для списка категорий

    $option_categoryes .= '<option value="" >-- Выберите категорию --</option>';
    // построение списка категорий
    foreach ($list_of_cats as $cat_name => $cat_array) {

        $option_categoryes .= '<optgroup label="' . $cat_name . '">';

        foreach ($cat_array as $opt_id => $opt_value) {
            //  Если номер категории равен переданному номеру категории в функции, то отмечаем этот <option> как selected
            if (($value['category'] != '') && ($opt_id == $value['category'])) {
                $category_checked = 'selected';
            } else {
                $category_checked = '';
            }
            // вывод полей
            $option_categoryes .= '<option ' . $category_checked . ' value="' . $opt_id . '" >' . $opt_value . '</option>' . "\n";
        }
        $option_categoryes .= '</optgroup>' . "\n";
    }
    echo $option_categoryes; // вывод списка категорий
}

//функция вывода списка объявлений
function print_advs($advs) {
    if ($advs) {
        foreach ($advs as $key => $value) {
            echo '<div class="row">';
//        в ссылке прописываем id объявления
            echo '<div class="k25"><a href="?id=' . $key . '">' . $value['advert_name'] . '</a></div><div class="k25">' . $value['price'] . ' руб.';
            echo '</div><div class="k25">' . $value['name'] . '</div>' . '<div class="k25"><a href="?del=' . $key . '">Удалить</a></div>';
            echo '</div>';
        }
    }
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Объявления</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            *{
                box-sizing: border-box;
            }
            .shell{
                width: 960px;
                margin: auto;
            }
            .row{
                width: 100%;
                clear: both;
            }
            .k100,.k50,.k25,.k75{
                float: left;
                padding: 0.5em;
            }
            .k50{
                width: 50%;
            }
            .k25{
                width: 25%;
            }
            .k75{
                width: 75%;
            }
            input[type="text"],input[type="tel"],input[type="email"],textarea{
                width: 50%;
            }
            .clear{
                clear: both;
            }
        </style>
    </head>

    <body>
        <div class="shell">
            <form method="POST">

                <div class="row">
                    <div class="k25"></div>
                    <div class="k75">
                        <div class="k25">Частное лицо<input type="radio" <?php echo ($value['type'] == 'private') ? 'checked' : ''; ?> name="type" value="private"></div>
                        <div class="k25">Компания<input type="radio" <?php echo ($value['type'] == 'company') ? 'checked' : ''; ?> name="type" value="company"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="k25">Ваше имя</div>
                    <div class="k75"><input type="text" required="" name="name" value="<?php echo $value['name']; ?>" ></div>
                </div>

                <div class="row">
                    <div class="k25">Электронная почта</div>
                    <div class="k75"><input type="email" required="" name="email" value="<?php echo $value['email']; ?>" ></div>
                </div>

                <div class="row">
                    <div class="k25"></div>
                    <div class="k75"><input type="checkbox" <?php echo $value['spam']; ?> name="spam" value="checked">Не получать уведомления на email</div>
                </div>

                <div class="row">
                    <div class="k25">Номер телефона</div>
                    <div class="k75"><input type="tel" required="" name="phone" value="<?php echo $value['phone']; ?>" ></div>
                </div>

                <div class="row">
                    <div class="k25">Город</div>
                    <div class="k75">
                        <select required="" name="city">
                            <?php get_cityes_list($cityes, $value); ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="k25">Категория</div>
                    <div class="k75">
                        <select name="category" required="">
                            <?php get_cat_list($categoryes, $value); ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="k25">Название объявления</div>
                    <div class="k75"><input type="text" required="" name="advert_name" value="<?php echo $value['advert_name']; ?>" ></div>
                </div>

                <div class="row">
                    <div class="k25">Описание объявления</div>
                    <div class="k75"><textarea required="" name="text"><?php echo $value['text']; ?></textarea></div>
                </div>

                <div class="row">
                    <div class="k25">Цена</div>
                    <div class="k75"><input type="text" required="" name="price" value="<?php echo $value['price']; ?>" > руб.</div>
                </div>

                <div class="row">
                    <div class="k25"></div>
                    <div class="k50">
                        <button type="submit">Добавить/обновить объявление</button>
                    </div>
                </div>

            </form>

            <div class="row">
                <hr>
                <?php print_advs($temp); ?>
            </div>
        </div>
    </body>
</html>