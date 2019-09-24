<?php

namespace Tests\Feature\DiarioApi;

use App\Models\LegacyEnrollment;
use App\Models\LegacyEvaluationRule;
use App\Models\LegacyRoundingTable;
use App\Models\LegacyValueRoundingTable;
use App_Model_MatriculaSituacao;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProgressionWithAverageCalculationWeightedRecoveryTest extends TestCase
{
    use DiarioApiFakeDataTestTrait, DiarioApiRequestTestTrait, DatabaseTransactions;

    /**
     * @var LegacyEnrollment
     */
    private $enrollment;

    public function setUp(): void
    {
        parent::setUp();
        $this->enrollment = $this->getProgressionWithAverageCalculationWeightedRecoveryTest();
    }

    /**
     * @return LegacyEnrollment
     */
    public function getProgressionWithAverageCalculationWeightedRecoveryTest()
    {
        $roundingTable = factory(LegacyRoundingTable::class, 'numeric')->create();
        factory(LegacyValueRoundingTable::class, 10)->create([
            'tabela_arredondamento_id' => $roundingTable->id,
        ]);

        $evaluationRule = factory(LegacyEvaluationRule::class, 'progressao-calculo-media-recuperacao-ponderada')->create([
            'tabela_arredondamento_id' => $roundingTable->id,
        ]);

        $enrollment = $this->getCommonFakeData($evaluationRule);

        return $enrollment;
    }

    public function testApprovedAfterAllScoreAndAbsencePosted()
    {
        $schoolClass = $this->enrollment->schoolClass;
        $school = $schoolClass->school;

        $this->createStages($school, 4);
        $this->createDisciplines($schoolClass, 2);

        $disciplines = $schoolClass->disciplines;

        $score = [
            1 => 8,
            2 => 8,
            3 => 8,
            4 => 8,
        ];

        $absence = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Aprovado', $response->situacao);
        }

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::APROVADO, $registration->refresh()->aprovado);
    }

    public function testReprovedPerAbsenceAfterAllScoreAndAbsencePosted()
    {
        $schoolClass = $this->enrollment->schoolClass;
        $school = $schoolClass->school;

        $this->createStages($school, 4);
        $this->createDisciplines($schoolClass, 2);

        $disciplines = $schoolClass->disciplines;

        $score = [
            1 => 8,
            2 => 8,
            3 => 8,
            4 => 8,
        ];

        $absence = [
            1 => 48,
            2 => 45,
            3 => 17,
            4 => 24,
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Retido', $response->situacao);
        }

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::REPROVADO_POR_FALTAS, $registration->refresh()->aprovado);
    }

    public function testStudyingAfterAllScoreAndAbsencePosted()
    {
        $schoolClass = $this->enrollment->schoolClass;
        $school = $schoolClass->school;

        $this->createStages($school, 4);
        $this->createDisciplines($schoolClass, 2);

        $disciplines = $schoolClass->disciplines;

        $score = [
            1 => 5,
            2 => 5,
            3 => 5,
            4 => 5,
        ];

        $absence = [
            1 => 3,
            2 => 3,
            3 => 3,
            4 => 3,
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Em exame', $response->situacao);
        }

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::EM_ANDAMENTO, $registration->refresh()->aprovado);
    }

    public function testApprovedAfterExamAllScoreAndAbsencePosted()
    {
        $schoolClass = $this->enrollment->schoolClass;
        $school = $schoolClass->school;

        $this->createStages($school, 4);
        $this->createDisciplines($schoolClass, 2);

        $disciplines = $schoolClass->disciplines;

        $score = [
            1 => 6,
            2 => 5,
            3 => 6,
            4 => 5,
        ];

        $absence = [
            1 => 4,
            2 => 7,
            3 => 5,
            4 => 2,
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Em exame', $response->situacao);
        }

        $score = [
            'Rc' => 8
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Aprovado após exame', $response->situacao);
        }

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::APROVADO, $registration->refresh()->aprovado);
    }

    public function testReprovedAfterExamAllScoreAndAbsencePosted()
    {
        $schoolClass = $this->enrollment->schoolClass;
        $school = $schoolClass->school;

        $this->createStages($school, 4);
        $this->createDisciplines($schoolClass, 2);

        $disciplines = $schoolClass->disciplines;

        $score = [
            1 => 6,
            2 => 5,
            3 => 6,
            4 => 5,
        ];

        $absence = [
            1 => 4,
            2 => 7,
            3 => 5,
            4 => 2,
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Em exame', $response->situacao);
        }

        $score = [
            'Rc' => 5
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Retido', $response->situacao);
        }

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::REPROVADO, $registration->refresh()->aprovado);
    }

    public function testInExamAfterAllScoreAndAbsencePostedWithAbsenceHigh()
    {
        $schoolClass = $this->enrollment->schoolClass;
        $school = $schoolClass->school;

        $this->createStages($school, 4);
        $this->createDisciplines($schoolClass, 8);

        $disciplines = $schoolClass->disciplines;

        $score = [
            1 => 6,
            2 => 5,
            3 => 6,
            4 => 5,
        ];

        $absence = [
            1 => 24,
            2 => 17,
            3 => 51,
            4 => 22,
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Em exame', $response->situacao);
        }

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::EM_ANDAMENTO, $registration->refresh()->aprovado);
    }

    public function testReprovedPerAbsenceAfterExamAllScoreAndAbsencePosted()
    {
        $schoolClass = $this->enrollment->schoolClass;
        $school = $schoolClass->school;

        $this->createStages($school, 4);
        $this->createDisciplines($schoolClass, 2);

        $disciplines = $schoolClass->disciplines;

        $score = [
            1 => 6,
            2 => 5,
            3 => 6,
            4 => 5,
        ];

        $absence = [
            1 => 32,
            2 => 27,
            3 => 51,
            4 => 32,
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Em exame', $response->situacao);
        }

        $score = [
            'Rc' => 8
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Retido', $response->situacao);
        }

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::REPROVADO_POR_FALTAS, $registration->refresh()->aprovado);
    }

    public function testStudyingAfterNotAllStageScoreAndAbsencePosted()
    {
        $schoolClass = $this->enrollment->schoolClass;
        $school = $schoolClass->school;

        $this->createStages($school, 4);
        $this->createDisciplines($schoolClass, 2);

        $disciplines = $schoolClass->disciplines;

        $score = [
            1 => 6,
            2 => 5,
        ];

        $absence = [
            1 => 2,
            2 => 2,
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Cursando', $response->situacao);
        }

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::EM_ANDAMENTO, $registration->refresh()->aprovado);
    }

    public function testStudyingAfterRemoveStageScoreAndAbsencePosted()
    {
        $schoolClass = $this->enrollment->schoolClass;
        $school = $schoolClass->school;

        $this->createStages($school, 4);
        $this->createDisciplines($schoolClass, 2);

        $disciplines = $schoolClass->disciplines;

        $score = [
            1 => 7,
            2 => 7,
            3 => 7,
            4 => 7
        ];

        $absence = [
            1 => 2,
            2 => 2,
            3 => 2,
            4 => 2
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Aprovado', $response->situacao);
        }

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::APROVADO, $registration->refresh()->aprovado);

        $randomDiscipline = $schoolClass->disciplines->random()->id;
        $response = $this->deleteAbsence($this->enrollment, $randomDiscipline, 4);
        $this->assertEquals('Cursando', $response->situacao);

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::EM_ANDAMENTO, $registration->refresh()->aprovado);
    }

    public function testErrorAfterRemoveNotLastStageScoreAndAbsencePosted()
    {
        $schoolClass = $this->enrollment->schoolClass;
        $school = $schoolClass->school;

        $this->createStages($school, 4);
        $this->createDisciplines($schoolClass, 2);

        $disciplines = $schoolClass->disciplines;

        $score = [
            1 => 7,
            2 => 7,
            3 => 7,
            4 => 7
        ];

        $absence = [
            1 => 2,
            2 => 2,
            3 => 2,
            4 => 2
        ];

        foreach ($disciplines as $discipline) {
            $this->postAbsenceForStages($absence, $discipline);
            $response = $this->postScoreForStages($score, $discipline);

            $this->assertEquals('Aprovado', $response->situacao);
        }

        $randomDiscipline = $schoolClass->disciplines->random()->id;
        $response = $this->deleteAbsence($this->enrollment, $randomDiscipline, 3);
        $this->assertTrue($response->any_error_msg);

        $registration = $this->enrollment->registration;
        $this->assertEquals(App_Model_MatriculaSituacao::APROVADO, $registration->refresh()->aprovado);
    }
}
