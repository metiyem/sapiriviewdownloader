<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopee Review Downloader</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            margin-top: 20px;
            text-align: center;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 16px;
        }
        input[type="submit"] {
            background-color: #4caf50;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        p {
            text-align: center;
            margin-top: 20px;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Shopee Review Downloader</h2>
        <form action="" method="POST">
            <label for="url">URL Produk Shopee:</label><br>
            <input type="text" id="url" name="url" required><br>
            <label for="jumlah">Jumlah ulasan yang ingin diambil:</label><br>
            <input type="number" id="jumlah" name="jumlah" required><br>
            <input type="submit" name="submit" value="Download Ulasan">
        </form>

    <?php
    if (isset($_POST['submit'])) {
        function get_all_shopee_reviews($url, $max_reviews) {
            $batch_size = 50; // Batas jumlah ulasan yang diambil dalam satu permintaan
            $all_reviews = [];
            $offset = 0;
            $total_reviews = 0;

            echo "<p>Proses pengambilan ulasan dimulai...</p>";

            while ($total_reviews < $max_reviews) {
                $current_url = $url . "&limit=" . $batch_size . "&offset=" . $offset;
                try {
                    $response = file_get_contents($current_url);
                    $data = json_decode($response, true);
                    $reviews = $data['data']['ratings'] ?? [];
                    if (empty($reviews)) {
                        break;
                    }

                    $remaining_reviews_needed = $max_reviews - $total_reviews;
                    $all_reviews = array_merge($all_reviews, array_slice($reviews, 0, $remaining_reviews_needed));
                    $total_reviews += count($reviews);
                    $offset += $batch_size;

                    ob_flush();
                    flush();

                    if ($total_reviews >= $max_reviews) {
                        break;
                    }
                } catch (Exception $e) {
                    echo "<p>Gagal mengambil data: " . $e->getMessage() . "</p>";
                    return [];
                }
            }

            echo "<p>Proses pengambilan ulasan selesai. Total ulasan yang diambil: $total_reviews</p>";
            return array_slice($all_reviews, 0, $max_reviews);
        }

        function save_reviews_to_xlsx($all_reviews, $filename='shopee_reviews.xlsx') {
            $data = [['Nama', 'Rating', 'Ulasan']];

            foreach ($all_reviews as $i => $review) {
                if ($i >= 500) {
                    break;
                }
                $star_rating = intval($review['rating_star'] ?? 0);
                $rating_text = "Di Beri : " . $star_rating . " dari 5 Bintang";
                $comment = trim($review['comment'] ?? '');
                if (empty($comment)) {
                    $comment = "Pelanggan tidak memberikan ulasan";
                }
                $data[] = [$review['author_username'] ?? 'Unknown', $rating_text, $comment];
            }

            $fp = fopen($filename, 'w');
            foreach ($data as $fields) {
                fputcsv($fp, $fields);
            }
            fclose($fp);
            echo "<p>[OK] Data ulasan telah disimpan ke dalam file 'shopee_reviews.xlsx'</p>";
            echo "<script>window.location.href = '$filename';</script>"; // Redirect to the file for download
        }

        // Mendapatkan URL dari input pengguna
        $url = $_POST['url'];

        // Mengekstrak shop_id dan item_id dari URL menggunakan regular expression
        preg_match('/\.(\d+)\.(\d+)/', $url, $matches);

        if (count($matches) == 3) {
            $shop_id = $matches[1];
            $item_id = $matches[2];

            // Membuat URL untuk mendapatkan ulasan menggunakan shop_id dan item_id
            $api_url = "https://shopee.co.id/api/v2/item/get_ratings?exclude_filter=1&filter=0&filter_size=0&flag=1&fold_filter=0&itemid=$item_id&limit=6&offset=0&relevant_reviews=false&request_source=2&shopid=$shop_id&tag_filter=&type=0&variation_filters=";

            // Mendapatkan semua ulasan menggunakan URL yang dibuat
            $max_reviews = $_POST['jumlah']; // Ambil jumlah ulasan yang ingin diambil dari input pengguna
            $all_reviews = get_all_shopee_reviews($api_url, $max_reviews);

            if (!empty($all_reviews)) {
                // Menyimpan ulasan ke dalam file XLSX
                save_reviews_to_xlsx($all_reviews);
            } else {
                echo "<p>Tidak ada ulasan yang ditemukan.</p>";
            }
        } else {
            echo "<p>Nomor tidak ditemukan dalam URL.</p>";
        }
    }
    ?>
</body>
</html>
