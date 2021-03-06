<?php

namespace frontend\controllers;

use Yii;
use common\models\User;
use common\models\Organization;
use common\models\OrganizationMember;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use frontend\models\JoinForm;


/**
 * OrganizationController implements the CRUD actions for Organization model.
 */
class OrganizationController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction ( $action ){
        if (Yii::$app->user->isGuest){
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.')); 
        }

        if (!isset( $_GET['id'] )){
            return true;
        }

        $request = Yii::$app->request;
        $OrganizationID = $request->get('id');

        $user = User::findIdentity(Yii::$app->user->identity->id);

        if ($user->isMember($OrganizationID)){
            return true;
        }
        else
        {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.')); 
        }
    }

    /**
     * Lists all Organization models.
     * @return mixed
     */
    public function actionIndex()
    {
        $user = User::findByUsername(Yii::$app->user->identity->username);

        $dataProvider = new ActiveDataProvider([
            'query' => $user->getOrganizations(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays join organization.
     *
     * @return mixed
     */
    public function actionJoin()
    {
        $model = new JoinForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $orgMember = new OrganizationMember();
            $orgMember->UserID = Yii::$app->user->identity->id;

            if (Organization::find()->where(['name' => $model->orgName])->exists()) {
                $orgMember->OrganizationID = Organization::find()->where(['name' => $model->orgName])->one()->ID;
                $orgMember->save();

                Yii::$app->session->setFlash('success', 'You successfully joined the organization');
            } else {
                Yii::$app->session->setFlash('error', 'Error Joining Organization');
            }

            return $this->refresh();
        } else {
            return $this->render('joinOrganization', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays a single Organization model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $org  = $this->findModel($id);

        $dataProvider = new ActiveDataProvider([
            'query' => $org->getRooms(),
        ]);

        $dataProvider1 = new ActiveDataProvider([
            'query' => $org->getBookings(),
        ]);

        return $this->render('view', [
            'model' => $this->findModel($id),
            'dataProvider' => $dataProvider,
            'dataProvider1' => $dataProvider1
        ]);
    }

    /**
     * Creates a new Organization model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Organization();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            $member = new OrganizationMember();
            $member->UserID = User::findByUsername(Yii::$app->user->identity->username)->id;
            $member->OrganizationID = $model->ID;
            $member->save();

            return $this->redirect(['view', 'id' => $model->ID]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Organization model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->ID]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Organization model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Organization model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Organization the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Organization::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
