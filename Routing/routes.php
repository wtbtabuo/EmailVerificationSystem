<?php

use Exceptions\AuthenticationFailureException;
use Helpers\ValidationHelper;
use Helpers\Authenticate;
use Models\ComputerPart;
use Response\FlashData;
use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Response\Render\RedirectRenderer;
use Response\Render\MediaRenderer;
use Database\DataAccess\DAOFactory;
use Response\Render\JSONRenderer;
use Routing\Route;
use Types\ValueType;
use Models\User;
use Scripts\MailSender;
use Helpers\Settings;


return [
    'login' => Route::create('login', function (): HTTPRenderer {
        return new HTMLRenderer('page/login');
    })->setMiddleware(['guest']),
    'form/login' => Route::create('form/login', function (): HTTPRenderer {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method!');
            $required_fields = [
                'email' => ValueType::EMAIL,
                'password' => ValueType::STRING,
            ];
            $validatedData = ValidationHelper::validateFields($required_fields, $_POST);

            Authenticate::authenticate($validatedData['email'], $validatedData['password']);

            $userDao = DAOFactory::getUserDAO();
            if (!$userDao->isVerifiedUser($validatedData['email'])) {
                $lasts = 900;

                $validatedData['expication'] = time() + $lasts;
                $url = Route::create('verify-email', function(){})->getSignedURL($validatedData);

                // メール送信の設定
                $toEmail = $driver = Settings::env('TO_EMAIL');
                $toName = 'No Reply';
                $subject = 'Login Authentification';
                $bodyText = $url; 

                // メール送信
                MailSender::sendCustomMail($toEmail, $toName, $subject, $bodyText);

                FlashData::setFlashData('success', 'Check your email and verify it');
                return new HTMLRenderer('page/login');
            }
            else {
                FlashData::setFlashData('success', 'Logged in successfully.');
                return new RedirectRenderer('login');
            }

        } catch (AuthenticationFailureException $e) {
            error_log($e->getMessage());

            FlashData::setFlashData('error', 'Failed to login, wrong email and/or password.');
            return new RedirectRenderer('login');
        } catch (\InvalidArgumentException $e) {
            error_log($e->getMessage());

            FlashData::setFlashData('error', 'Invalid Data.');
            return new RedirectRenderer('login');
        } catch (Exception $e) {
            error_log($e->getMessage());

            FlashData::setFlashData('error', 'An error occurred.');
            return new RedirectRenderer('login');
        }
    })->setMiddleware(['guest']),
    'register' => Route::create('register', function (): HTTPRenderer {
        return new HTMLRenderer('page/register');
    })->setMiddleware(['guest']),
    'form/register' => Route::create('form/register', function (): HTTPRenderer {
        try {
            // リクエストメソッドがPOSTかどうかをチェックします
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method!');

            $required_fields = [
                'username' => ValueType::STRING,
                'email' => ValueType::EMAIL,
                'password' => ValueType::PASSWORD,
                'confirm_password' => ValueType::PASSWORD,
                'company' => ValueType::STRING,
            ];

            $userDao = DAOFactory::getUserDAO();

            // シンプルな検証
            $validatedData = ValidationHelper::validateFields($required_fields, $_POST);

            if($validatedData['confirm_password'] !== $validatedData['password']){
                FlashData::setFlashData('error', 'Invalid Password!');
                return new RedirectRenderer('register');
            }

            // Eメールは一意でなければならないので、Eメールがすでに使用されていないか確認します
            if($userDao->getByEmail($validatedData['email'])){
                FlashData::setFlashData('error', 'Email is already in use!');
                return new RedirectRenderer('register');
            }

            // 新しいUserオブジェクトを作成します
            $user = new User(
                username: $validatedData['username'],
                email: $validatedData['email'],
                company: $validatedData['company']
            );

            // データベースにユーザーを作成しようとします
            $success = $userDao->create($user, $validatedData['password']);

            if (!$success) throw new Exception('Failed to create new user!');

            // ユーザーログイン
            Authenticate::loginAsUser($user);

            FlashData::setFlashData('success', 'Account successfully created.');
            return new RedirectRenderer('random/part');
        } catch (\InvalidArgumentException $e) {
            error_log($e->getMessage());

            FlashData::setFlashData('error', 'Invalid Data.');
            return new RedirectRenderer('register');
        } catch (Exception $e) {
            error_log($e->getMessage());

            FlashData::setFlashData('error', 'An error occurred.');
            return new RedirectRenderer('register');
        }
    })->setMiddleware(['guest']),
    'logout' => Route::create('logout', function (): HTTPRenderer {
        Authenticate::logoutUser();
        FlashData::setFlashData('success', 'Logged out.');
        return new RedirectRenderer('random/part');
    })->setMiddleware(['auth']),
    'random/part' => Route::create('random/part', function (): HTTPRenderer {
        $partDao = DAOFactory::getComputerPartDAO();
        $part = $partDao->getRandom();

        if($part === null) throw new Exception('No parts are available!');

        return new HTMLRenderer('component/computer-part-card', ['part'=>$part]);
    }),
    'parts' => Route::create('parts', function (): HTTPRenderer {
        // IDの検証
        $id = ValidationHelper::integer($_GET['id']??null);

        $partDao = DAOFactory::getComputerPartDAO();
        $part = $partDao->getById($id);

        if($part === null) throw new Exception('Specified part was not found!');

        return new HTMLRenderer('component/computer-part-card', ['part'=>$part]);
    }),
    'update/part' => Route::create('update/part', function (): HTTPRenderer {
        $user = Authenticate::getAuthenticatedUser();
        $part = null;
        $partDao = DAOFactory::getComputerPartDAO();
        if(isset($_GET['id'])){
            $id = ValidationHelper::integer($_GET['id']);
            $part = $partDao->getById($id);
            if($user->getId() !== $part->getSubmittedById()){
                FlashData::setFlashData('error', 'Only the author can edit this computer part.');
                return new RedirectRenderer('register');
            }
        }
        return new HTMLRenderer('component/update-computer-part',['part'=>$part]);
    })->setMiddleware(['auth']),
    'form/update/part' => Route::create('form/update/part', function (): HTTPRenderer {
        try {
            // クエストメソッドがPOSTかどうかをチェックします
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method!');
            }

            $required_fields = [
                'name' => ValueType::STRING,
                'type' => ValueType::STRING,
                'brand' => ValueType::STRING,
                'modelNumber' => ValueType::STRING,
                'releaseDate' => ValueType::DATE,
                'description' => ValueType::STRING,
                'performanceScore' => ValueType::INT,
                'marketPrice' => ValueType::FLOAT,
                'rsm' => ValueType::FLOAT,
                'powerConsumptionW' => ValueType::FLOAT,
                'lengthM' => ValueType::FLOAT,
                'widthM' => ValueType::FLOAT,
                'heightM' => ValueType::FLOAT,
                'lifespan' => ValueType::INT,
            ];

            $partDao = DAOFactory::getComputerPartDAO();

            // 入力に対する単純な認証。実際のシナリオでは、要件を満たす完全な認証が必要になることがあります
            $validatedData = ValidationHelper::validateFields($required_fields, $_POST);

            $user = Authenticate::getAuthenticatedUser();

            // idが設定されている場合は、認証を行います
            if(isset($_POST['id'])){
                $validatedData['id'] = ValidationHelper::integer($_POST['id']);
                $currentPart = $partDao->getById($_POST['id']);
                if($currentPart === null || $user->getId() !== $currentPart->getSubmittedById()){
                    return new JSONRenderer(['status' => 'error', 'message' => 'Invalid Data Permissions!']);
                }
            }

            $validatedData['submitted_by_id'] = $user->getId();

            $part = new ComputerPart(...$validatedData);

            error_log(json_encode($part->toArray(), JSON_PRETTY_PRINT));

            // 新しい部品情報でデータベースの更新を試みます。
            // 別の方法として、createOrUpdateを実行することもできます。
            if(isset($validatedData['id'])) $success = $partDao->update($part);
            else $success = $partDao->create($part);

            if (!$success) {
                throw new Exception('Database update failed!');
            }

            return new JSONRenderer(['status' => 'success', 'message' => 'Part updated successfully', 'id'=>$part->getId()]);
        } catch (\InvalidArgumentException $e) {
            error_log($e->getMessage());
            return new JSONRenderer(['status' => 'error', 'message' => 'Invalid data.']);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return new JSONRenderer(['status' => 'error', 'message' => 'An error occurred.']);
        }
    })->setMiddleware(['auth']),

    'verify-email'=> Route::create('verify-email', function(): HTTPRenderer{
        // このURLは署名を必要とするため、URLが正しい署名を持つ場合にのみ、この最終ルートコードに到達します。
        $required_fields = [
            'email' => ValueType::STRING,
            'password' => ValueType::STRING, // 本番環境では、有効なファイルパスに対してバリデーションを行いますが、ファイルパスの単純な文字列チェックを行います。
        ];

        $validatedData = ValidationHelper::validateFields($required_fields, $_GET);

        return new RedirectRenderer('login');
    })->setMiddleware(['signature']),
    'generate-url'=> Route::create('generate-url', function(): HTTPRenderer{
        $required_fields = [
            'email' => ValueType::STRING,
        ];

        $validatedData = ValidationHelper::validateFields($required_fields, $_GET);

        if(isset($_GET['lasts'])){
            $validatedData['expiration'] = time() + ValidationHelper::integer($_GET['lasts']);
        }

        return new JSONRenderer(['url'=>Route::create('verify-email', function(){})->getSignedURL($validatedData)]);
    }),
];