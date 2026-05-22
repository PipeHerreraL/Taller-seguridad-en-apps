<?php

declare(strict_types=1);

namespace Tests;

use App\Helpers\Validator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * ValidatorTest
 * Verifica todas las reglas de validación y sanitización.
 * Usa atributos PHP (#[Test]) compatibles con PHPUnit 11 y 12.
 */
class ValidatorTest extends TestCase
{
    private Validator $v;

    protected function setUp(): void
    {
        $this->v = new Validator;
    }

    #[Test]
    public function required_falla_con_cadena_vacia(): void
    {
        $this->v->required('', 'nombre');
        $this->assertArrayHasKey('nombre', $this->v->getErrors());
    }

    #[Test]
    public function required_falla_con_solo_espacios(): void
    {
        $this->v->required('   ', 'nombre');
        $this->assertArrayHasKey('nombre', $this->v->getErrors());
    }

    #[Test]
    public function required_pasa_con_valor_valido(): void
    {
        $this->v->required('Juan', 'nombre');
        $this->assertArrayNotHasKey('nombre', $this->v->getErrors());
    }

    // ── email ─────────────────────────────────────────────────
    #[Test]
    public function email_falla_con_formato_invalido(): void
    {
        $this->v->email('no-es-un-email');
        $this->assertArrayHasKey('email', $this->v->getErrors());
    }

    #[Test]
    public function email_falla_sin_dominio(): void
    {
        $this->v->email('usuario@');
        $this->assertArrayHasKey('email', $this->v->getErrors());
    }

    #[Test]
    public function email_pasa_con_direccion_valida(): void
    {
        $this->v->email('usuario@ejemplo.com');
        $this->assertArrayNotHasKey('email', $this->v->getErrors());
    }

    // ── min_length ───────────────────────────────────────────
    #[Test]
    public function min_length_falla_cuando_es_mas_corto(): void
    {
        $this->v->minLength('12345', 'password', 8);
        $this->assertArrayHasKey('password', $this->v->getErrors());
    }

    #[Test]
    public function min_length_pasa_cuando_es_suficiente(): void
    {
        $this->v->minLength('12345678', 'password', 8);
        $this->assertArrayNotHasKey('password', $this->v->getErrors());
    }

    // ── date ─────────────────────────────────────────────────
    #[Test]
    public function date_falla_con_formato_incorrecto(): void
    {
        $this->v->date('31-12-2020', 'fecha_nacimiento');
        $this->assertArrayHasKey('fecha_nacimiento', $this->v->getErrors());
    }

    #[Test]
    public function date_falla_con_fecha_futura(): void
    {
        $futura = (new \DateTime('+1 day'))->format('Y-m-d');
        $this->v->date($futura, 'fecha_nacimiento');
        $this->assertArrayHasKey('fecha_nacimiento', $this->v->getErrors());
    }

    #[Test]
    public function date_pasa_con_fecha_valida_del_pasado(): void
    {
        $this->v->date('2000-01-01', 'fecha_nacimiento');
        $this->assertArrayNotHasKey('fecha_nacimiento', $this->v->getErrors());
    }

    // ── phone ─────────────────────────────────────────────────
    #[Test]
    public function phone_falla_con_letras(): void
    {
        $this->v->phone('300abc1234');
        $this->assertArrayHasKey('telefono', $this->v->getErrors());
    }

    #[Test]
    public function phone_falla_con_menos_de_7_digitos(): void
    {
        $this->v->phone('123456');
        $this->assertArrayHasKey('telefono', $this->v->getErrors());
    }

    #[Test]
    public function phone_pasa_con_numero_valido(): void
    {
        $this->v->phone('+573000000000');
        $this->assertArrayNotHasKey('telefono', $this->v->getErrors());
    }

    #[Test]
    public function phone_pasa_sin_prefijo_internacional(): void
    {
        $this->v->phone('3000000000');
        $this->assertArrayNotHasKey('telefono', $this->v->getErrors());
    }

    // ── no_special_chars ──────────────────────────────────────
    #[Test]
    public function no_special_chars_falla_con_angulo_abre(): void
    {
        $this->v->noSpecialChars('<Juan', 'nombre');
        $this->assertArrayHasKey('nombre', $this->v->getErrors());
    }

    #[Test]
    public function no_special_chars_falla_con_comilla_simple(): void
    {
        $this->v->noSpecialChars("O'Connor", 'apellido');
        $this->assertArrayHasKey('apellido', $this->v->getErrors());
    }

    #[Test]
    public function no_special_chars_falla_con_comilla_doble(): void
    {
        $this->v->noSpecialChars('Juan"ito', 'nombre');
        $this->assertArrayHasKey('nombre', $this->v->getErrors());
    }

    #[Test]
    public function no_special_chars_pasa_con_nombre_normal(): void
    {
        $this->v->noSpecialChars('Juan José', 'nombre');
        $this->assertArrayNotHasKey('nombre', $this->v->getErrors());
    }

    // ── in_list ───────────────────────────────────────────────
    #[Test]
    public function in_list_falla_con_valor_no_permitido(): void
    {
        $this->v->inList('Z+', 'tipo_sangre', ['A+', 'O-']);
        $this->assertArrayHasKey('tipo_sangre', $this->v->getErrors());
    }

    #[Test]
    public function in_list_pasa_con_valor_permitido(): void
    {
        $this->v->inList('O-', 'tipo_sangre', ['A+', 'O-']);
        $this->assertArrayNotHasKey('tipo_sangre', $this->v->getErrors());
    }

    // ── sanitize ──────────────────────────────────────────────
    #[Test]
    public function sanitize_escapa_etiquetas_html(): void
    {
        $dirty = '<script>alert("xss")</script>';
        $clean = Validator::sanitize($dirty);
        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringContainsString('&lt;script&gt;', $clean);
    }

    #[Test]
    public function sanitize_elimina_espacios_extremos(): void
    {
        $dirty = '   texto limpio   ';
        $clean = Validator::sanitize($dirty);
        $this->assertEquals('texto limpio', $clean);
    }

    #[Test]
    public function sanitize_escapa_comillas(): void
    {
        $dirty = 'O\'Connor "Test"';
        $clean = Validator::sanitize($dirty);
        $this->assertStringNotContainsString('\'', $clean);
        $this->assertStringNotContainsString('"', $clean);
        $this->assertStringContainsString('&apos;', $clean);
        $this->assertStringContainsString('&quot;', $clean);
    }

    // ── helper status ─────────────────────────────────────────
    #[Test]
    public function has_errors_retorna_false_sin_errores(): void
    {
        $this->assertFalse($this->v->hasErrors());
    }

    #[Test]
    public function has_errors_retorna_true_con_errores(): void
    {
        $this->v->required('', 'nombre');
        $this->assertTrue($this->v->hasErrors());
    }

    #[Test]
    public function get_errors_retorna_array_de_mensajes(): void
    {
        $this->v->required('', 'nombre');
        $errors = $this->v->getErrors();
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('nombre', $errors);
    }
}
