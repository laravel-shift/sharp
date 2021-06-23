<?php

namespace Code16\Sharp\Tests\Unit\Form\Fields\Formatters;

use Code16\Sharp\Form\Fields\Formatters\MarkdownFormatter;
use Code16\Sharp\Form\Fields\Formatters\UploadFormatter;
use Code16\Sharp\Form\Fields\SharpFormField;
use Code16\Sharp\Form\Fields\SharpFormMarkdownField;
use Code16\Sharp\Tests\SharpTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarkdownFormatterTest extends SharpTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake("local");
        Storage::fake("public");
    }

    /** @test */
    function we_can_format_a_text_value_to_front()
    {
        $formatter = new MarkdownFormatter;
        $field = SharpFormMarkdownField::make("md");
        $value = Str::random() . "\n\n" . Str::random();

        $this->assertEquals(
            [
                "text" => $value, 
                "files" => []
            ], 
            $formatter->toFront($field, $value)
        );
    }

    /** @test */
    function when_text_has_an_embedded_upload_the_files_array_is_handled_to_front()
    {
        UploadedFile::fake()
            ->image("test.png")
            ->storeAs("data", "test.png", "local");
        
        $value = <<<EOT
            Some content text before
            
            <x-sharp-media 
                disk="local"
                path="data/test.png"
                name="test.png"
            ></x-sharp-media>

            Some content text after
        EOT;

        $result = (new MarkdownFormatter)
            ->toFront(
                SharpFormMarkdownField::make("md"), 
                $value
            );
        
        $this->assertEquals($value, $result["text"]);
        $this->assertCount(1, $result["files"]);
        $this->assertArraySubset(
            [
                "path" => "data/test.png",
                "name" => "test.png",
                "disk" => "local",
                "size" => 91,
            ], 
            $result["files"][0]
        );
        $this->assertStringStartsWith(
            "/storage/thumbnails/data/200-200/test.png",
            $result["files"][0]["thumbnail"]
        );
    }

    /** @test */
    function the_files_array_is_properly_handled_to_front_when_containing_filters()
    {
        UploadedFile::fake()
            ->image("test.png")
            ->storeAs("data", "test.png", "local");

        $value = <<<EOT
            <x-sharp-media 
                disk="local"
                path="data/test.png"
                filter-crop=".1,.2,.3,.4"
                filter-rotate="45"
                name="test.png"
            ></x-sharp-media>
        EOT;

        $result = (new MarkdownFormatter)
            ->toFront(
                SharpFormMarkdownField::make("md"),
                $value
            );

        $filters = [
            "crop" => [
                "x" => 0.1,
                "y" => 0.2,
                "width" => 0.3,
                "height" => 0.4,
            ],
            "rotate" => [
                "angle" => 45
            ]
        ];
        
        $this->assertArraySubset(
            [
                "path" => "data/test.png",
                "filters" => $filters
            ],
            $result["files"][0]
        );
        $this->assertMatchesRegularExpression(
            "#/storage/thumbnails/data/200-200_.*/test.png#",
            $result["files"][0]["thumbnail"]
        );
    }

    /** @test */
    function when_text_has_multiple_embedded_uploads_the_files_array_is_handled_to_front()
    {
        UploadedFile::fake()->image("test.png")->storeAs("data", "test.png", "local");
        UploadedFile::fake()->image("test2.png")->storeAs("data", "test2.png", "local");
        UploadedFile::fake()->image("test3.png")->storeAs("data", "test3.png", "local");

        $value = <<<EOT
            <x-sharp-media 
                disk="local"
                path="data/test.png"
                name="test.png"
            ></x-sharp-media>
            <x-sharp-media 
                disk="local"
                path="data/test2.png"
                name="test2.png"
            ></x-sharp-media>
            <x-sharp-media 
                disk="local"
                path="data/test3.png"
                name="test3.png"
            ></x-sharp-media>
        EOT;

        $this->assertCount(
            3, 
            (new MarkdownFormatter)->toFront(
                SharpFormMarkdownField::make("md"), 
                $value
            )["files"]
        );
    }

    /** @test */
    function we_can_format_a_text_value_from_front()
    {
        $value = Str::random();

        $this->assertEquals(
            $value, 
            (new MarkdownFormatter)->fromFront(
                SharpFormMarkdownField::make("md"), 
                "attribute", 
                ["text" => $value]
            )
        );
    }

    /** @test */
    function we_store_newly_uploaded_files_from_front()
    {
        app()->bind(UploadFormatter::class, function() {
            return new class extends UploadFormatter {
                function fromFront(SharpFormField $field, string $attribute, $value)
                {
                    return [
                        "file_name" => "data/uploaded_test.png",
                        "disk" => "local"
                    ];
                }
            };
        });

        $value = <<<EOT
            Some content text before
            
            <x-sharp-media 
                name="test.png"
                uploaded="true"
            ></x-sharp-media>

            Some content text after
        EOT;
        
        $result = (new MarkdownFormatter)
            ->fromFront(
                SharpFormMarkdownField::make("md")
                    ->setStorageDisk("local")
                    ->setStorageBasePath("data"),
                "attribute",
                [
                    "text" => $value,
                    "files" => [
                        [
                            "name" => "test.png",
                            "uploaded" => true
                        ]
                    ]
                ]
            );
        
        $this->assertStringContainsString(
            "Some content text before",
            $result
        );

        $this->assertStringContainsString(
            "Some content text after",
            $result
        );

        $this->assertStringContainsString(
            '<x-sharp-media name="uploaded_test.png" uploaded="true" path="data/uploaded_test.png" disk="local"></x-sharp-media>',
            $result
        );
    }

    /** @test */
    function files_are_handled_for_a_localized_markdown()
    {
        $formatter = new MarkdownFormatter;
        $field = SharpFormMarkdownField::make("md")->setLocalized();
        $value = [
            "fr" => <<<EOT
                <x-sharp-media 
                    disk="local"
                    path="data/test_fr.png"
                    name="test_fr.png"
                ></x-sharp-media>
                <x-sharp-media 
                    disk="local"
                    path="data/test2_fr.png"
                    name="test2_fr.png"
                ></x-sharp-media>
            EOT,
            "en" => <<<EOT
                <x-sharp-media 
                    disk="local"
                    path="data/test_en.png"
                    name="test_en.png"
                ></x-sharp-media>
            EOT
        ];

        $this->assertCount(3, $formatter->toFront($field, $value)["files"]);
    }

}