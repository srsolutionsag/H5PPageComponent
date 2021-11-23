<?php declare(strict_types=1);

/* Copyright (c) 2021 Thibeau Fuhrer <thf@studer-raimann.ch> Extended GPL, see docs/LICENSE */

/**
 * Class ilH5PExportHelper
 *
 * @author Thibeau Fuhrer <thf@studer-raimann.ch>
 */
class ilH5PExportHelper
{
    /**
     * @var string regex pattern that matches the XML tag 'ExportFile' and
     *             therefore the attributes of this tag.
     */
    private const MANIFEST_EXPORT_FILE_REGEX = '/(?=ExportFile).[^>]*/';

    /**
     * @var string regex pattern that matches the content of an XML tag's
     *             'Path' attribute (between "").
     */
    private const MANIFEST_EXPORT_PATH_REGEX = '/(?<=Path=")(.*)(?=")/';

    /**
     * @var string regex pattern that matches the whole XML 'Plugged' Tag
     *             whose 'PluginName' attribute is of this plugin.
     */
    private const EXPORT_FILE_PLUGGED_H5P_CONTENT = '/<Plugged PluginName="H5PPageComponent"[\s\S]*?<\/Plugged>/';

    /**
     * Holds the absolute path to the temporary export directory.
     *
     * @var string
     */
    protected string $absolute_dir;

    /**
     * Holds an unparsed XML string of the current export manifest.
     *
     * @var string
     */
    protected string $xml_manifest;

    /**
     * ilH5PExportHelper Constructor
     *
     * @param string $xml_manifest
     * @param string $absolute_dir
     */
    public function __construct(string $xml_manifest, string $absolute_dir)
    {
        $this->xml_manifest = $xml_manifest;
        $this->absolute_dir = $absolute_dir;
    }

    /**
     * This method is an extremely hacky way to remove H5P content within
     * COPages during an import or export.
     *
     * What it does is essentially scan the import or export manifest for
     * 'ExportFile' attributes which point to an export file relative to
     * the given absolute directory. Then each of these export files is
     * searched for a 'Plugged' XML property of this plugin and removes it.
     */
    public function replacePluggedContents(string $replacement = null) : void
    {
        preg_match_all(self::MANIFEST_EXPORT_FILE_REGEX, $this->xml_manifest, $export_file_tags);

        // abort if the current manifest doesn't contain
        // any export-file tags.
        if (empty($export_file_tags[0])) {
            return;
        }

        foreach ($export_file_tags[0] as $export_file_tag) {
            preg_match(self::MANIFEST_EXPORT_PATH_REGEX, $export_file_tag, $relative_file_paths);

            if (!empty($relative_file_paths[0])) {
                $absolute_file_path = "$this->absolute_dir/$relative_file_paths[0]";

                // remove the plugged xml property from the current
                // file's content, in order to prohibit H5P exports.
                $original_content  = @file_get_contents($absolute_file_path);
                $processed_content = preg_replace(
                    self::EXPORT_FILE_PLUGGED_H5P_CONTENT,
                    $replacement ?? '',
                    $original_content
                );

                // only adopt the processed content if necessary.
                if ($original_content !== $processed_content) {
                    @file_put_contents($absolute_file_path, $processed_content);
                }
            }
        }
    }
}