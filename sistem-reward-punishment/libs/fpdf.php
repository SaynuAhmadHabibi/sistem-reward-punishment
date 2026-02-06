<?php
define('FPDF_VERSION','1.85');

// Define font path if not already defined
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', dirname(__FILE__).'/font/');
}

class FPDF
{
protected $page;               // current page number
protected $n;                  // current object number
protected $offsets;            // array of object offsets
protected $buffer;             // buffer holding in-memory PDF
protected $pages;              // array containing pages
protected $state;              // current document state
protected $compress;           // compression flag
protected $k;                  // scale factor (number of points in user unit)
protected $DefOrientation;     // default orientation
protected $CurOrientation;     // current orientation
protected $StdPageSizes;       // standard page sizes
protected $DefPageSize;        // default page size
protected $CurPageSize;        // current page size
protected $CurRotation;        // current page rotation
protected $PageInfo;           // page-related data
protected $wPt,$hPt;           // dimensions of current page in points
protected $w,$h;               // dimensions of current page in user unit
protected $lMargin;            // left margin
protected $tMargin;            // top margin
protected $rMargin;            // right margin
protected $bMargin;            // page break margin
protected $cMargin;            // cell margin
protected $x,$y;               // current position in user unit
protected $lasth;              // height of last printed cell
protected $LineWidth;          // line width in user unit
protected $fontpath;           // directory containing fonts
protected $CoreFonts;          // array of core font names
protected $fonts;              // array of used fonts
protected $FontFiles;          // array of font files
protected $encodings;          // array of encodings
protected $cmaps;              // array of ToUnicode CMaps
protected $FontFamily;         // current font family
protected $FontStyle;          // current font style
protected $FontSizePt;         // current font size in points
protected $FontSize;           // current font size in user unit
protected $CurrentFont;        // current font info
protected $UnderlinePosition;  // current underline position
protected $UnderlineThickness; // current underline thickness
protected $TextColor;          // current text color
protected $DrawColor;          // current draw color
protected $FillColor;          // current fill color
protected $ColorFlag;          // indicates whether fill and text colors are different
protected $WithAlpha;          // indicates whether alpha channel is used
protected $ws;                 // word spacing
protected $images;             // array of used images
protected $PageLinks;          // array of links in pages
protected $links;              // array of internal links
protected $AutoPageBreak;      // automatic page breaking
protected $PageBreakTrigger;   // threshold used to trigger page breaks
protected $InHeader;           // flag set when processing header
protected $InFooter;           // flag set when processing footer
protected $AliasNbPages;       // alias for total number of pages
protected $ZoomMode;           // zoom display mode
protected $LayoutMode;         // layout display mode
protected $metadata;           // document properties
protected $PDFVersion;         // PDF version number

/*******************************************************************************
*                               Public methods                                 *
*******************************************************************************/

function __construct($orientation='P', $unit='mm', $size='A4')
{
    // Some checks
    $this->_dochecks();
    // Initialization of properties
    $this->state = 0;
    $this->page = 0;
    $this->n = 2;
    $this->buffer = '';
    $this->pages = array();
    $this->PageInfo = array();
    $this->PageLinks = array(); // DITAMBAHKAN: Inisialisasi array PageLinks
    $this->fonts = array();
    $this->FontFiles = array();
    $this->encodings = array();
    $this->cmaps = array();
    $this->images = array();
    $this->links = array();
    $this->InHeader = false;
    $this->InFooter = false;
    $this->lasth = 0;
    $this->FontFamily = '';
    $this->FontStyle = '';
    $this->FontSizePt = 12;
    $this->UnderlinePosition = -100;
    $this->UnderlineThickness = 50;
    $this->DrawColor = '0 G';
    $this->FillColor = '0 g';
    $this->TextColor = '0 g';
    $this->ColorFlag = false;
    $this->WithAlpha = false;
    $this->ws = 0;
    // Font path
    if(defined('FPDF_FONTPATH'))
    {
        $this->fontpath = FPDF_FONTPATH;
        if(substr($this->fontpath,-1)!='/' && substr($this->fontpath,-1)!='\\')
            $this->fontpath .= '/';
    }
    elseif(is_dir(dirname(__FILE__).'/font'))
        $this->fontpath = dirname(__FILE__).'/font/';
    else
        $this->fontpath = '';
    // Core fonts
    $this->CoreFonts = array('courier','helvetica','symbol','times','zapfdingbats'); // DIPERBAIKI: Array sederhana
    // Scale factor
    if($unit=='pt')
        $this->k = 1;
    elseif($unit=='mm')
        $this->k = 72/25.4;
    elseif($unit=='cm')
        $this->k = 72/2.54;
    elseif($unit=='in')
        $this->k = 72;
    else
        $this->Error('Incorrect unit: '.$unit);
    // Page sizes
    $this->StdPageSizes = array('a3'=>array(841.89,1190.55),'a4'=>array(595.28,841.89),
    'a5'=>array(420.94,595.28),'letter'=>array(612,792),'legal'=>array(612,1008));
    $size = $this->_getpagesize($size);
    $this->DefPageSize = $size;
    $this->CurPageSize = $size;
    // Page orientation
    $orientation = strtolower($orientation);
    if($orientation=='p' || $orientation=='portrait')
    {
        $this->DefOrientation = 'P';
        $this->w = $size[0];
        $this->h = $size[1];
    }
    elseif($orientation=='l' || $orientation=='landscape')
    {
        $this->DefOrientation = 'L';
        $this->w = $size[1];
        $this->h = $size[0];
    }
    else
        $this->Error('Incorrect orientation: '.$orientation);
    $this->CurOrientation = $this->DefOrientation;
    $this->wPt = $this->w*$this->k;
    $this->hPt = $this->h*$this->k;
    // Page rotation
    $this->CurRotation = 0;
    // Page margins (1 cm)
    $margin = 28.35/$this->k;
    $this->SetMargins($margin,$margin);
    // Interior cell margin (1 mm)
    $this->cMargin = $margin/10;
    // Line width (0.2 mm)
    $this->SetLineWidth(.567/$this->k);
    // Automatic page break
    $this->SetAutoPageBreak(true,2*$margin);
    // Default display mode
    $this->SetDisplayMode('default');
    // Enable compression
    $this->SetCompression(true);
    // Metadata
    $this->metadata = array('Producer'=>'FPDF '.FPDF_VERSION);
    $this->PDFVersion = '1.3';
}

function SetMargins($left, $top, $right=null)
{
    // Set left, top and right margins
    $this->lMargin = $left;
    $this->tMargin = $top;
    if($right===null)
        $right = $left;
    $this->rMargin = $right;
}

function SetLeftMargin($margin)
{
    // Set left margin
    $this->lMargin = $margin;
    if($this->page>0 && $this->x<$margin)
        $this->x = $margin;
}

function SetTopMargin($margin)
{
    // Set top margin
    $this->tMargin = $margin;
}

function SetRightMargin($margin)
{
    // Set right margin
    $this->rMargin = $margin;
}

function SetAutoPageBreak($auto, $margin=0)
{
    // Set auto page break mode and triggering margin
    $this->AutoPageBreak = $auto;
    $this->bMargin = $margin;
    $this->PageBreakTrigger = $this->h-$margin;
}

function SetDisplayMode($zoom, $layout='default')
{
    // Set display mode in viewer
    if($zoom=='fullpage' || $zoom=='fullwidth' || $zoom=='real' || $zoom=='default' || !is_string($zoom))
        $this->ZoomMode = $zoom;
    else
        $this->Error('Incorrect zoom display mode: '.$zoom);
    if($layout=='single' || $layout=='continuous' || $layout=='two' || $layout=='default')
        $this->LayoutMode = $layout;
    else
        $this->Error('Incorrect layout display mode: '.$layout);
}

function SetCompression($compress)
{
    // Set page compression
    if(function_exists('gzcompress'))
        $this->compress = $compress;
    else
        $this->compress = false;
}

function SetTitle($title, $isUTF8=false)
{
    $this->metadata['Title'] = $isUTF8
        ? $title
        : mb_convert_encoding($title, 'ISO-8859-1', 'UTF-8');
}

function SetAuthor($author, $isUTF8=false)
{
    $this->metadata['Author'] = $isUTF8
        ? $author
        : mb_convert_encoding($author, 'ISO-8859-1', 'UTF-8');
}

function SetSubject($subject, $isUTF8=false)
{
    $this->metadata['Subject'] = $isUTF8
        ? $subject
        : mb_convert_encoding($subject, 'ISO-8859-1', 'UTF-8');
}

function SetKeywords($keywords, $isUTF8=false)
{
    $this->metadata['Keywords'] = $isUTF8
        ? $keywords
        : mb_convert_encoding($keywords, 'ISO-8859-1', 'UTF-8');
}

function SetCreator($creator, $isUTF8=false)
{
    $this->metadata['Creator'] = $isUTF8
        ? $creator
        : mb_convert_encoding($creator, 'ISO-8859-1', 'UTF-8');
}


function AliasNbPages($alias='{nb}')
{
    // Define an alias for total number of pages
    $this->AliasNbPages = $alias;
}

function Error($msg)
{
    // Fatal error
    throw new Exception('FPDF error: '.$msg);
}

function Close()
{
    // Terminate document
    if($this->state==3)
        return;
    if($this->page==0)
        $this->AddPage();
    // Page footer
    $this->InFooter = true;
    $this->Footer();
    $this->InFooter = false;
    // Close page
    $this->_endpage();
    // Close document
    $this->_enddoc();
}

function AddPage($orientation='', $size='', $rotation=0)
{
    // Start a new page
    if($this->state==3)
        $this->Error('The document is closed');
    $family = $this->FontFamily;
    $style = $this->FontStyle.($this->UnderlinePosition ? 'U' : '');
    $fontsize = $this->FontSizePt;
    $lw = $this->LineWidth;
    $dc = $this->DrawColor;
    $fc = $this->FillColor;
    $tc = $this->TextColor;
    $cf = $this->ColorFlag;
    if($this->page>0)
    {
        // Page footer
        $this->InFooter = true;
        $this->Footer();
        $this->InFooter = false;
        // Close page
        $this->_endpage();
    }
    // Start new page
    $this->_beginpage($orientation,$size,$rotation);
    // Set line cap style to square
    $this->_out('2 J');
    // Set line width
    $this->LineWidth = $lw;
    $this->_out(sprintf('%.2F w',$lw*$this->k));
    // Set font
    if($family)
        $this->SetFont($family,$style,$fontsize);
    // Set colors
    $this->DrawColor = $dc;
    if($dc!='0 G')
        $this->_out($dc);
    $this->FillColor = $fc;
    if($fc!='0 g')
        $this->_out($fc);
    $this->TextColor = $tc;
    $this->ColorFlag = $cf;
    // Page header
    $this->InHeader = true;
    $this->Header();
    $this->InHeader = false;
    // Restore line width
    if($this->LineWidth!=$lw)
    {
        $this->LineWidth = $lw;
        $this->_out(sprintf('%.2F w',$lw*$this->k));
    }
    // Restore font
    if($family)
        $this->SetFont($family,$style,$fontsize);
    // Restore colors
    if($this->DrawColor!=$dc)
    {
        $this->DrawColor = $dc;
        $this->_out($dc);
    }
    if($this->FillColor!=$fc)
    {
        $this->FillColor = $fc;
        $this->_out($fc);
    }
    $this->TextColor = $tc;
    $this->ColorFlag = $cf;
}

function Header()
{
    // To be implemented in your own inherited class
}

function Footer()
{
    // To be implemented in your own inherited class
}

function PageNo()
{
    // Get current page number
    return $this->page;
}

function SetDrawColor($r, $g=null, $b=null)
{
    // Set color for all stroking operations
    if(($r==0 && $g==0 && $b==0) || $g===null)
        $this->DrawColor = sprintf('%.3F G',$r/255);
    else
        $this->DrawColor = sprintf('%.3F %.3F %.3F RG',$r/255,$g/255,$b/255);
    if($this->page>0)
        $this->_out($this->DrawColor);
}

function SetFillColor($r, $g=null, $b=null)
{
    // Set color for all filling operations
    if(($r==0 && $g==0 && $b==0) || $g===null)
        $this->FillColor = sprintf('%.3F g',$r/255);
    else
        $this->FillColor = sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
    $this->ColorFlag = ($this->FillColor!=$this->TextColor);
    if($this->page>0)
        $this->_out($this->FillColor);
}

function SetTextColor($r, $g=null, $b=null)
{
    // Set color for text
    if(($r==0 && $g==0 && $b==0) || $g===null)
        $this->TextColor = sprintf('%.3F g',$r/255);
    else
        $this->TextColor = sprintf('%.3F %.3F %.3F rg',$r/255,$g/255,$b/255);
    $this->ColorFlag = ($this->FillColor!=$this->TextColor);
}

function GetStringWidth($s)
{
    // Get width of a string in the current font
    $s = (string)$s;
    $cw = $this->CurrentFont['cw'];
    $w = 0;
    $l = strlen($s);
    for($i=0;$i<$l;$i++)
        $w += isset($cw[$s[$i]]) ? $cw[$s[$i]] : 0; // DIPERBAIKI: Cek isset
    return $w*$this->FontSize/1000;
}

function SetLineWidth($width)
{
    // Set line width
    $this->LineWidth = $width;
    if($this->page>0)
        $this->_out(sprintf('%.2F w',$width*$this->k));
}

function Line($x1, $y1, $x2, $y2)
{
    // Draw a line
    $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S',$x1*$this->k,($this->h-$y1)*$this->k,$x2*$this->k,($this->h-$y2)*$this->k));
}

function Rect($x, $y, $w, $h, $style='')
{
    // Draw a rectangle
    if($style=='F')
        $op = 'f';
    elseif($style=='FD' || $style=='DF')
        $op = 'B';
    else
        $op = 'S';
    $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s',$x*$this->k,($this->h-$y)*$this->k,$w*$this->k,-$h*$this->k,$op));
}

function AddFont($family, $style='', $file='')
{
    // Add a TrueType, OpenType or Type1 font
    $family = strtolower($family);
    if($file=='')
        $file = str_replace(' ','',$family).strtolower($style).'.php';
    $style = strtoupper($style);
    if($style=='IB')
        $style = 'BI';
    $fontkey = $family.$style;
    if(isset($this->fonts[$fontkey]))
        return;
    $info = $this->_loadfont($file);
    $info['i'] = count($this->fonts)+1;
    if(!empty($info['file']))
    {
        // Embedded font
        if($info['type']=='TrueType')
            $this->FontFiles[$info['file']] = array('length1'=>$info['originalsize']);
        else
            $this->FontFiles[$info['file']] = array('length1'=>$info['size1'], 'length2'=>$info['size2']);
    }
    $this->fonts[$fontkey] = $info;
}

function SetFont($family, $style='', $size=0)
{
    // Select a font; size given in points
    if($family=='')
        $family = $this->FontFamily;
    else
        $family = strtolower($family);
    $style = strtoupper($style);
    if(strpos($style,'U')!==false)
    {
        $this->UnderlinePosition = -100;
        $this->UnderlineThickness = 50;
        $style = str_replace('U','',$style);
    }
    else
    {
        if($this->UnderlinePosition!=-100)
        {
            $this->UnderlinePosition = -100;
            $this->UnderlineThickness = 50;
        }
    }
    if($style=='IB')
        $style = 'BI';
    if($size==0)
        $size = $this->FontSizePt;
    // Test if font is already selected
    if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size)
        return;
    // Test if font is already loaded
    $fontkey = $family.$style;
    if(!isset($this->fonts[$fontkey]))
    {
        // Test if one of the core fonts
        if($family=='arial')
            $family = 'helvetica';
        if(in_array($family,$this->CoreFonts))
        {
            if($family=='symbol' || $family=='zapfdingbats')
                $style = '';
            $fontkey = $family.$style;
            if(!isset($this->fonts[$fontkey]))
               $this->AddFont($family,$style);
        }
        else
            $this->Error('Undefined font: '.$family.' '.$style);
    }
    // Select it
    $this->FontFamily = $family;
    $this->FontStyle = $style;
    $this->FontSizePt = $size;
    $this->FontSize = $size/$this->k;
    $this->CurrentFont = $this->fonts[$fontkey];
    if($this->page>0)
        $this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
}

function SetFontSize($size)
{
    // Set font size in points
    if($this->FontSizePt==$size)
        return;
    $this->FontSizePt = $size;
    $this->FontSize = $size/$this->k;
    if($this->page>0)
        $this->_out(sprintf('BT /F%d %.2F Tf ET',$this->CurrentFont['i'],$this->FontSizePt));
}

function AddLink()
{
    // Create a new internal link
    $n = count($this->links)+1;
    $this->links[$n] = array(0, 0);
    return $n;
}

function SetLink($link, $y=0, $page=-1)
{
    // Set destination of internal link
    if($y==-1)
        $y = $this->y;
    if($page==-1)
        $page = $this->page;
    $this->links[$link] = array($page, $y);
}

function Link($x, $y, $w, $h, $link)
{
    // Put a link on the page
    if(!isset($this->PageLinks[$this->page]))
        $this->PageLinks[$this->page] = array();
    $this->PageLinks[$this->page][] = array($x*$this->k, $this->hPt-$y*$this->k, $w*$this->k, $h*$this->k, $link);
}

function Text($x, $y, $txt)
{
    // Output a string
    if($this->ColorFlag)
        $this->_out('q '.$this->TextColor);
    $s = sprintf('BT %.2F %.2F Td (%s) Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
    $this->_out($s);
    if($this->ColorFlag)
        $this->_out(' Q');
}

function AcceptPageBreak()
{
    // Accept automatic page break or not
    return $this->AutoPageBreak;
}

function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
{
    // Output a cell
    $k = $this->k;
    if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
    {
        $x = $this->x;
        $ws = $this->ws;
        if($ws>0)
        {
            $this->ws = 0;
            $this->_out('0 Tw');
        }
        $this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
        $this->x = $x;
        if($ws>0)
        {
            $this->ws = $ws;
            $this->_out(sprintf('%.3F Tw',$ws*$k));
        }
    }
    if($w==0)
        $w = $this->w-$this->rMargin-$this->x;
    $s = '';
    if($fill || $border==1)
    {
        if($fill)
            $op = ($border==1) ? 'B' : 'f';
        else
            $op = 'S';
        $s = sprintf('%.2F %.2F %.2F %.2F re %s ',$this->x*$k,($this->h-$this->y)*$k,$w*$k,-$h*$k,$op);
    }
    if(is_string($border))
    {
        $x = $this->x;
        $y = $this->y;
        if(strpos($border,'L')!==false)
            $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,$x*$k,($this->h-($y+$h))*$k);
        if(strpos($border,'T')!==false)
            $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-$y)*$k);
        if(strpos($border,'R')!==false)
            $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',($x+$w)*$k,($this->h-$y)*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
        if(strpos($border,'B')!==false)
            $s .= sprintf('%.2F %.2F m %.2F %.2F l S ',$x*$k,($this->h-($y+$h))*$k,($x+$w)*$k,($this->h-($y+$h))*$k);
    }
    if($txt!=='')
    {
        if(!is_string($align))
            $align = '';
        if($align=='R')
            $dx = $w-$this->cMargin-$this->GetStringWidth($txt);
        elseif($align=='C')
            $dx = ($w-$this->GetStringWidth($txt))/2;
        else
            $dx = $this->cMargin;
        if($this->ColorFlag)
            $s .= 'q '.$this->TextColor.' ';
        $txt2 = str_replace(')','\\)',str_replace('(','\\(',str_replace('\\','\\\\',$txt)));
        $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET',($this->x+$dx)*$k,($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k,$txt2);
        if($this->ColorFlag)
            $s .= ' Q';
        if($link)
            $this->Link($this->x+$dx,$this->y+.5*$h-.5*$this->FontSize,$this->GetStringWidth($txt),$this->FontSize,$link);
    }
    if($s)
        $this->_out($s);
    $this->lasth = $h;
    if($ln>0)
    {
        // Go to next line
        $this->y += $h;
        if($ln==1)
            $this->x = $this->lMargin;
    }
    else
        $this->x += $w;
}

function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
{
    // Output text with automatic or explicit line breaks
    $cw = $this->CurrentFont['cw'];
    if($w==0)
        $w = $this->w-$this->rMargin-$this->x;
    $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
    $s = str_replace("\r",'',$txt);
    $nb = strlen($s);
    if($nb>0 && $s[$nb-1]=="\n")
        $nb--;
    $b = 0;
    if($border)
    {
        if($border==1)
        {
            $border = 'LTRB';
            $b = 'LRT';
            $b2 = 'LR';
        }
        else
        {
            $b2 = '';
            if(strpos($border,'L')!==false)
                $b2 .= 'L';
            if(strpos($border,'R')!==false)
                $b2 .= 'R';
            $b = (strpos($border,'T')!==false) ? $b2.'T' : $b2;
        }
    }
    $sep = -1;
    $i = 0;
    $j = 0;
    $l = 0;
    $ns = 0;
    $nl = 1;
    while($i<$nb)
    {
        // Get next character
        $c = $s[$i];
        if($c=="\n")
        {
            // Explicit line break
            if($this->ws>0)
            {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
            $i++;
            $sep = -1;
            $j = $i;
            $l = 0;
            $ns = 0;
            $nl++;
            if($border && $nl==2)
                $b = $b2;
            continue;
        }
        if($c==' ')
        {
            $sep = $i;
            $ls = $l;
            $ns++;
        }
        $l += isset($cw[$c]) ? $cw[$c] : 0; // DIPERBAIKI: Cek isset
        if($l>$wmax)
        {
            // Automatic line break
            if($sep==-1)
            {
                if($i==$j)
                    $i++;
                if($this->ws>0)
                {
                    $this->ws = 0;
                    $this->_out('0 Tw');
                }
                $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
            }
            else
            {
                if($align=='J')
                {
                    $this->ws = ($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
                    $this->_out(sprintf('%.3F Tw',$this->ws*$this->k));
                }
                $this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
                $i = $sep+1;
            }
            $sep = -1;
            $j = $i;
            $l = 0;
            $ns = 0;
            $nl++;
            if($border && $nl==2)
                $b = $b2;
        }
        else
            $i++;
    }
    // Last chunk
    if($this->ws>0)
    {
        $this->ws = 0;
        $this->_out('0 Tw');
    }
    if($border && strpos($border,'B')!==false)
        $b .= 'B';
    $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
    $this->x = $this->lMargin;
}

function Write($h, $txt, $link='')
{
    // Output text in flowing mode
    $cw = $this->CurrentFont['cw'];
    $w = $this->w-$this->rMargin-$this->x;
    $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
    $s = str_replace("\r",'',$txt);
    $nb = strlen($s);
    $sep = -1;
    $i = 0;
    $j = 0;
    $l = 0;
    $nl = 1;
    while($i<$nb)
    {
        // Get next character
        $c = $s[$i];
        if($c=="\n")
        {
            // Explicit line break
            $this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',false,$link);
            $i++;
            $sep = -1;
            $j = $i;
            $l = 0;
            if($nl==1)
            {
                $this->x = $this->lMargin;
                $w = $this->w-$this->rMargin-$this->x;
                $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
            }
            $nl++;
            continue;
        }
        if($c==' ')
            $sep = $i;
        $l += isset($cw[$c]) ? $cw[$c] : 0; // DIPERBAIKI: Cek isset
        if($l>$wmax)
        {
            // Automatic line break
            if($sep==-1)
            {
                if($this->x>$this->lMargin)
                {
                    // Move to next line
                    $this->x = $this->lMargin;
                    $this->y += $h;
                    $w = $this->w-$this->rMargin-$this->x;
                    $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
                    $i++;
                    $nl++;
                    continue;
                }
                if($i==$j)
                    $i++;
                $this->Cell($w,$h,substr($s,$j,$i-$j),0,2,'',false,$link);
            }
            else
            {
                $this->Cell($w,$h,substr($s,$j,$sep-$j),0,2,'',false,$link);
                $i = $sep+1;
            }
            $sep = -1;
            $j = $i;
            $l = 0;
            if($nl==1)
            {
                $this->x = $this->lMargin;
                $w = $this->w-$this->rMargin-$this->x;
                $wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
            }
            $nl++;
        }
        else
            $i++;
    }
    // Last chunk
    if($i!=$j)
        $this->Cell($l/1000*$this->FontSize,$h,substr($s,$j),0,0,'',false,$link);
}

function Ln($h=null)
{
    // Line feed; default value is the last cell height
    if($h===null)
        $h = $this->lasth;
    $this->x = $this->lMargin;
    $this->y += $h;
}

function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='')
{
    // Put an image on the page
    if($file=='')
        $this->Error('Image file name is empty');
    if(!isset($this->images[$file]))
    {
        // First use of this image, get info
        if($type=='')
        {
            $pos = strrpos($file,'.');
            if(!$pos)
                $this->Error('Image file has no extension and no type was specified: '.$file);
            $type = substr($file,$pos+1);
        }
        $type = strtolower($type);
        if($type=='jpeg')
            $type = 'jpg';
        $mtd = '_parse'.$type;
        if(!method_exists($this,$mtd))
            $this->Error('Unsupported image type: '.$type);
        $info = $this->$mtd($file);
        $info['i'] = count($this->images)+1;
        $this->images[$file] = $info;
    }
    else
        $info = $this->images[$file];

    // Automatic width and height calculation if needed
    if($w==0 && $h==0)
    {
        // Put image at 96 dpi
        $w = -96;
        $h = -96;
    }
    if($w<0)
        $w = -$info['w']*72/$w/$this->k;
    if($h<0)
        $h = -$info['h']*72/$h/$this->k;
    if($w==0)
        $w = $h*$info['w']/$info['h'];
    if($h==0)
        $h = $w*$info['h']/$info['w'];

    // Flowing mode
    if($y===null)
    {
        if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
        {
            // Automatic page break
            $x2 = $this->x;
            $this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
            $this->x = $x2;
        }
        $y = $this->y;
        $this->y += $h;
    }

    if($x===null)
        $x = $this->x;
    $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
    if($link)
        $this->Link($x,$y,$w,$h,$link);
}

function GetPageWidth()
{
    // Get current page width
    return $this->w;
}

function GetPageHeight()
{
    // Get current page height
    return $this->h;
}

function GetX()
{
    // Get x position
    return $this->x;
}

function SetX($x)
{
    // Set x position
    if($x>=0)
        $this->x = $x;
    else
        $this->x = $this->w+$x;
}

function GetY()
{
    // Get y position
    return $this->y;
}

function SetY($y)
{
    // Set y position and reset x
    $this->x = $this->lMargin;
    if($y>=0)
        $this->y = $y;
    else
        $this->y = $this->h+$y;
}

function SetXY($x, $y)
{
    // Set x and y positions
    $this->SetX($x);
    $this->SetY($y);
}

function Output($dest='', $name='', $isUTF8=false)
{
    // Output PDF to some destination
    $this->Close();
    if(strlen($name)==1 && strlen($dest)!=1)
    {
        // Fix parameter order
        $tmp = $dest;
        $dest = $name;
        $name = $tmp;
    }
    if($dest=='')
        $dest = 'I';
    if($name=='')
        $name = 'doc.pdf';
    switch(strtoupper($dest))
    {
        case 'I':
            // Send to standard output
            $this->_checkoutput();
            if(PHP_SAPI!='cli')
            {
                // We send to a browser
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; '.$this->_httpencode('filename',$name,$isUTF8));
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
            }
            echo $this->buffer;
            break;
        case 'D':
            // Download file
            $this->_checkoutput();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; '.$this->_httpencode('filename',$name,$isUTF8));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $this->buffer;
            break;
        case 'F':
            // Save to local file
            $f = fopen($name,'wb');
            if(!$f)
                $this->Error('Unable to create output file: '.$name);
            fwrite($f,$this->buffer,strlen($this->buffer));
            fclose($f);
            break;
        case 'S':
            // Return as a string
            return $this->buffer;
        default:
            $this->Error('Incorrect output destination: '.$dest);
    }
    return '';
}

/*******************************************************************************
*                              Protected methods                               *
*******************************************************************************/

protected function _dochecks()
{
    // Force numeric locale (Windows + PHP 8 fix)
    setlocale(LC_NUMERIC, 'C');

    // Locale-independent float check
    if (sprintf('%.1F', 1.0) !== '1.0') {
        $this->Error(
            'The locale settings are not compatible with FPDF. ' .
            'Decimal separator must be a dot.'
        );
    }
}

protected function _checkoutput()
{
    if(PHP_SAPI!='cli')
    {
        if(headers_sent($file,$line))
            $this->Error("Some data has already been output, can't send PDF file (output started at $file:$line)");
    }
    if(ob_get_length())
    {
        // The output buffer is not empty
        if(preg_match('/^(\xEF\xBB\xBF)?\s*$/',ob_get_contents()))
        {
            // It contains only a UTF-8 BOM and/or whitespace, let's clean it
            ob_clean();
        }
        else
            $this->Error("Some data has already been output, can't send PDF file");
    }
}

protected function _getpagesize($size)
{
    if(is_string($size))
    {
        $size = strtolower($size);
        if(!isset($this->StdPageSizes[$size]))
            $this->Error('Unknown page size: '.$size);
        $s = $this->StdPageSizes[$size];
        return array($s[0]/$this->k, $s[1]/$this->k);
    }
    else
    {
        if($size[0]>$size[1])
            return array($size[1], $size[0]);
        else
            return $size;
    }
}

protected function _beginpage($orientation, $size, $rotation)
{
    $this->page++;
    $this->pages[$this->page] = '';
    $this->state = 2;
    $this->x = $this->lMargin;
    $this->y = $this->tMargin;
    $this->FontFamily = '';
    // Check page size and orientation
    if($orientation=='')
        $orientation = $this->DefOrientation;
    else
        $orientation = strtoupper($orientation);
    if($size=='')
        $size = $this->DefPageSize;
    else
        $size = $this->_getpagesize($size);
    if($orientation!=$this->DefOrientation || $size[0]!=$this->DefPageSize[0] || $size[1]!=$this->DefPageSize[1])
    {
        // New size or orientation
        if($orientation=='P')
        {
            $this->w = $size[0];
            $this->h = $size[1];
        }
        else
        {
            $this->w = $size[1];
            $this->h = $size[0];
        }
        $this->wPt = $this->w*$this->k;
        $this->hPt = $this->h*$this->k;
        $this->PageBreakTrigger = $this->h-$this->bMargin;
        $this->CurOrientation = $orientation;
        $this->CurPageSize = $size;
    }
    if($rotation!=0)
    {
        if($rotation%90!=0)
            $this->Error('Incorrect rotation value: '.$rotation);
        $this->CurRotation = $rotation;
    }
    $this->PageInfo[$this->page]['size'] = array($this->wPt, $this->hPt);
    $this->PageInfo[$this->page]['rotation'] = $rotation;
}

protected function _endpage()
{
    $this->state = 1;
}

protected function _loadfont($file)
{
    // Load a font definition file
    include($this->fontpath.$file);
    if(!isset($name))
        $this->Error('Could not include font definition file');
    return get_defined_vars();
}

protected function _escape($s)
{
    // Escape special characters in strings
    $s = str_replace('\\','\\\\',$s);
    $s = str_replace('(','\\(',$s);
    $s = str_replace(')','\\)',$s);
    $s = str_replace("\r",'\\r',$s);
    return $s;
}

protected function _textstring($s)
{
    // Format a text string
    return '('.$this->_escape($s).')';
}

protected function _httpencode($param, $value, $isUTF8)
{
    // Encode HTTP header field parameter (PHP 8 safe)
    if(!$isUTF8) {
        $value = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
    }

    if (isset($_SERVER['HTTP_USER_AGENT']) &&
        strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
        return $param.'="'.rawurlencode($value).'"';
    }

    return $param."*=UTF-8''".rawurlencode($value);
}


protected function _put($s)
{
    $this->pages[$this->page] .= $s."\n";
}

protected function _out($s)
{
    if($this->state==2)
        $this->_put($s);
    elseif($this->state==1)
        $this->Error('The document is closed');
    elseif($this->state==0)
        $this->Error('No page has been added yet');
    elseif($this->state==3)
        $this->Error('The document is currently being created');
}

/*******************************************************************************
*                                                                              *
*                              PHP extension methods                           *
*                                                                              *
*******************************************************************************/

protected function _parsejpg($file)
{
    // Extract info from a JPEG file
    $a = getimagesize($file);
    if(!$a)
        $this->Error('Missing or incorrect image file: '.$file);
    if($a[2]!=2)
        $this->Error('Not a JPEG file: '.$file);
    if(!isset($a['channels']) || $a['channels']==3)
        $colspace = 'DeviceRGB';
    elseif($a['channels']==4)
        $colspace = 'DeviceCMYK';
    else
        $colspace = 'DeviceGray';
    $bpc = isset($a['bits']) ? $a['bits'] : 8;
    $data = file_get_contents($file);
    return array('w'=>$a[0], 'h'=>$a[1], 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'DCTDecode', 'data'=>$data);
}

protected function _parsepng($file)
{
    // Extract info from a PNG file
    $f = fopen($file,'rb');
    if(!$f)
        $this->Error('Can\'t open image file: '.$file);
    $info = $this->_parsepngstream($f,$file);
    fclose($f);
    return $info;
}

protected function _parsepngstream($f, $file)
{
    // Check signature
    if($this->_readstream($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
        $this->Error('Not a PNG file: '.$file);

    // Read header chunk
    $this->_readstream($f,4);
    if($this->_readstream($f,4)!='IHDR')
        $this->Error('Incorrect PNG file: '.$file);
    $w = $this->_readint($f);
    $h = $this->_readint($f);
    $bpc = ord($this->_readstream($f,1));
    if($bpc>8)
        $this->Error('16-bit depth not supported: '.$file);
    $ct = ord($this->_readstream($f,1));
    if($ct==0 || $ct==4)
        $colspace = 'DeviceGray';
    elseif($ct==2 || $ct==6)
        $colspace = 'DeviceRGB';
    elseif($ct==3)
        $colspace = 'Indexed';
    else
        $this->Error('Unknown color type: '.$file);
    if(ord($this->_readstream($f,1))!=0)
        $this->Error('Unknown compression method: '.$file);
    if(ord($this->_readstream($f,1))!=0)
        $this->Error('Unknown filter method: '.$file);
    if(ord($this->_readstream($f,1))!=0)
        $this->Error('Interlacing not supported: '.$file);
    $this->_readstream($f,4);
    $dp = '/Predictor 15 /Colors '.($colspace=='DeviceRGB' ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w;

    // Scan chunks looking for palette, transparency and image data
    $pal = '';
    $trns = '';
    $data = '';
    do
    {
        $n = $this->_readint($f);
        $type = $this->_readstream($f,4);
        if($type=='PLTE')
        {
            // Read palette
            $pal = $this->_readstream($f,$n);
            $this->_readstream($f,4);
        }
        elseif($type=='tRNS')
        {
            // Read transparency info
            $t = $this->_readstream($f,$n);
            if($ct==0)
                $trns = array(ord(substr($t,1,1)));
            elseif($ct==2)
                $trns = array(ord(substr($t,1,1)), ord(substr($t,3,1)), ord(substr($t,5,1)));
            else
            {
                $pos = strpos($t,chr(0));
                if($pos!==false)
                    $trns = array($pos);
            }
            $this->_readstream($f,4);
        }
        elseif($type=='IDAT')
        {
            // Read image data block
            $data .= $this->_readstream($f,$n);
            $this->_readstream($f,4);
        }
        elseif($type=='IEND')
            break;
        else
            $this->_readstream($f,$n+4);
    }
    while($n);

    if($colspace=='Indexed' && empty($pal))
        $this->Error('Missing palette in '.$file);
    $info = array('w'=>$w, 'h'=>$h, 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'FlateDecode', 'dp'=>$dp, 'pal'=>$pal, 'trns'=>$trns);
    if($ct>=4)
    {
        // Extract alpha channel
        if(!function_exists('gzuncompress'))
            $this->Error('Zlib not available, can\'t handle alpha channel: '.$file);
        $data = gzuncompress($data);
        $color = '';
        $alpha = '';
        if($ct==4)
        {
            // Gray image with alpha
            $len = 2*$w;
            for($i=0;$i<$h;$i++)
            {
                $pos = (1+$len)*$i;
                $color .= $data[$pos];
                $alpha .= $data[$pos];
                $line = substr($data,$pos+1,$len);
                $color .= preg_replace('/(.)./s','$1',$line);
                $alpha .= preg_replace('/.(.)/s','$1',$line);
            }
        }
        else
        {
            // RGB image with alpha
            $len = 4*$w;
            for($i=0;$i<$h;$i++)
            {
                $pos = (1+$len)*$i;
                $color .= $data[$pos];
                $alpha .= $data[$pos];
                $line = substr($data,$pos+1,$len);
                $color .= preg_replace('/(.{3})./s','$1',$line);
                $alpha .= preg_replace('/.{3}(.)/s','$1',$line);
            }
        }
        unset($data);
        $data = gzcompress($color);
        $info['smask'] = gzcompress($alpha);
        $this->WithAlpha = true;
        if($this->PDFVersion<'1.4')
            $this->PDFVersion = '1.4';
    }
    $info['data'] = $data;
    return $info;
}

protected function _readstream($f, $n)
{
    // Read n bytes from stream
    $res = '';
    while($n>0 && !feof($f))
    {
        $s = fread($f,$n);
        if($s===false)
            $this->Error('Error while reading stream');
        $n -= strlen($s);
        $res .= $s;
    }
    if($n>0)
        $this->Error('Unexpected end of stream');
    return $res;
}

protected function _readint($f)
{
    // Read a 4-byte integer from stream
    $a = unpack('Ni',$this->_readstream($f,4));
    return $a['i'];
}

protected function _parsegif($file)
{
    // Extract info from a GIF file (via PNG conversion)
    if(!function_exists('imagepng'))
        $this->Error('GD extension is required for GIF support');
    if(!function_exists('imagecreatefromgif'))
        $this->Error('GD has no GIF read support');
    $im = imagecreatefromgif($file);
    if(!$im)
        $this->Error('Missing or incorrect image file: '.$file);
    imageinterlace($im,0);
    $f = @fopen('php://temp','rb+');
    if($f)
    {
        // Perform conversion in memory
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        fwrite($f,$data);
        rewind($f);
        $info = $this->_parsepngstream($f,$file);
        fclose($f);
    }
    else
    {
        // Use temporary file
        $tmp = tempnam('.','gif');
        if(!$tmp)
            $this->Error('Unable to create a temporary file');
        if(!imagepng($im,$tmp))
            $this->Error('Error while saving to temporary file');
        $info = $this->_parsepng($tmp);
        unlink($tmp);
    }
    imagedestroy($im);
    return $info;
}

protected function _newobj()
{
    // Begin a new object
    $this->n++;
    $this->offsets[$this->n] = strlen($this->buffer);
    $this->_out($this->n.' 0 obj');
}

protected function _putstream($s)
{
    $this->_out('stream');
    $this->_out($s);
    $this->_out('endstream');
}

protected function _putheader()
{
    $this->_out('%PDF-'.$this->PDFVersion);
}

protected function _puttrailer()
{
    $this->_out('/Size '.($this->n+1));
    $this->_out('/Root 1 0 R');
    $this->_out('/Info '.$this->n.' 0 R');
}

protected function _enddoc()
{
    $this->_putheader();
    $this->_putpages();
    $this->_putresources();
    // Info
    $this->_newobj();
    $this->_out('<<');
    $this->_putmetadata();
    $this->_out('/Producer '.$this->_textstring('FPDF '.FPDF_VERSION));
    if(isset($this->metadata['Title']))
        $this->_out('/Title '.$this->_textstring($this->metadata['Title']));
    if(isset($this->metadata['Subject']))
        $this->_out('/Subject '.$this->_textstring($this->metadata['Subject']));
    if(isset($this->metadata['Author']))
        $this->_out('/Author '.$this->_textstring($this->metadata['Author']));
    if(isset($this->metadata['Keywords']))
        $this->_out('/Keywords '.$this->_textstring($this->metadata['Keywords']));
    if(isset($this->metadata['Creator']))
        $this->_out('/Creator '.$this->_textstring($this->metadata['Creator']));
    $this->_out('/CreationDate '.$this->_textstring('D:'.date('YmdHis')));
    $this->_out('>>');
    $this->_out('endobj');
    // Catalog
    $this->_newobj();
    $this->_out('<<');
    $this->_putcatalog();
    $this->_out('>>');
    $this->_out('endobj');
    // Cross-ref
    $o = strlen($this->buffer);
    $this->_out('xref');
    $this->_out('0 '.($this->n+1));
    $this->_out('0000000000 65535 f ');
    for($i=1;$i<=$this->n;$i++)
        $this->_out(sprintf('%010d 00000 n ',$this->offsets[$i]));
    // Trailer
    $this->_out('trailer');
    $this->_out('<<');
    $this->_puttrailer();
    $this->_out('>>');
    $this->_out('startxref');
    $this->_out($o);
    $this->_out('%%EOF');
    $this->state = 3;
}

protected function _putmetadata()
{
    // To be implemented in your own inherited class
}

protected function _putcatalog()
{
    $this->_out('/Type /Catalog');
    $this->_out('/Pages 1 0 R');
    if($this->ZoomMode=='fullpage')
        $this->_out('/OpenAction [3 0 R /Fit]');
    elseif($this->ZoomMode=='fullwidth')
        $this->_out('/OpenAction [3 0 R /FitH null]');
    elseif($this->ZoomMode=='real')
        $this->_out('/OpenAction [3 0 R /XYZ null null 1]');
    elseif(!is_string($this->ZoomMode))
        $this->_out('/OpenAction [3 0 R /XYZ null null '.sprintf('%.2F',$this->ZoomMode/100).']');
    if($this->LayoutMode=='single')
        $this->_out('/PageLayout /SinglePage');
    elseif($this->LayoutMode=='continuous')
        $this->_out('/PageLayout /OneColumn');
    elseif($this->LayoutMode=='two')
        $this->_out('/PageLayout /TwoColumnLeft');
}

protected function _putpages()
{
    $nb = $this->page;
    for($n=1;$n<=$nb;$n++)
    {
        // Page
        $this->_newobj();
        $this->_out('<</Type /Page');
        $this->_out('/Parent 1 0 R');
        if(isset($this->PageInfo[$n]['size']))
            $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->PageInfo[$n]['size'][0],$this->PageInfo[$n]['size'][1]));
        if(isset($this->PageInfo[$n]['rotation']))
            $this->_out('/Rotate '.$this->PageInfo[$n]['rotation']);
        $this->_out('/Resources 2 0 R');
        if(!empty($this->PageLinks[$n]))
        {
            $s = '/Annots [';
            foreach($this->PageLinks[$n] as $pl)
                $s .= $pl[5].' 0 R ';
            $s .= ']';
            $this->_out($s);
        }
        if($this->WithAlpha)
            $this->_out('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
        $this->_out('/Contents '.($this->n+1).' 0 R>>');
        $this->_out('endobj');
        // Page content
        $p = ($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
        $this->_newobj();
        $this->_out('<<');
        if($this->compress)
            $this->_out('/Filter /FlateDecode');
        $this->_out('/Length '.strlen($p));
        $this->_out('>>');
        $this->_putstream($p);
        $this->_out('endobj');
    }
    // Pages root
    $this->_newobj(1);
    $this->_out('<</Type /Pages');
    $kids = '/Kids [';
    for($i=0;$i<$nb;$i++)
        $kids .= (3+2*$i).' 0 R ';
    $this->_out($kids.']');
    $this->_out('/Count '.$nb);
    $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->wPt,$this->hPt));
    $this->_out('>>');
    $this->_out('endobj');
}

protected function _putfonts()
{
    $nf=$this->n;
    foreach($this->fonts as $font)
    {
        // Font object
        $this->_newobj();
        $this->_out('<</Type /Font');
        $this->_out('/BaseFont /'.$font['name']);
        $this->_out('/Subtype /Type1');
        if($font['type']=='core')
        {
            // Standard font
            $this->_out('/Encoding /WinAnsiEncoding');
        }
        else
        {
            // Additional font
            $this->_out('/FirstChar 32');
            $this->_out('/LastChar 255');
            $this->_out('/Widths '.($this->n+1).' 0 R');
            $this->_out('/FontDescriptor '.($this->n+2).' 0 R');
        }
        $this->_out('>>');
        $this->_out('endobj');
        if($font['type']!='core')
        {
            // Widths
            $this->_newobj();
            $cw = $font['cw'];
            $s = '[';
            for($i=32;$i<=255;$i++)
                $s .= $cw[chr($i)].' ';
            $this->_out($s.']');
            $this->_out('endobj');
            // Descriptor
            $this->_newobj();
            $s = '<</Type /FontDescriptor /FontName /'.$font['name'];
            foreach($font['desc'] as $k=>$v)
                $s .= ' /'.$k.' '.$v;
            if(!empty($font['file']))
                $s .= ' /FontFile'.($font['type']=='Type1' ? '' : '2').' '.$this->FontFiles[$font['file']]['n'].' 0 R';
            $this->_out($s.'>>');
            $this->_out('endobj');
        }
    }
}

protected function _putimages()
{
    foreach(array_keys($this->images) as $file)
    {
        $this->_putimage($this->images[$file]);
        unset($this->images[$file]['data']);
        unset($this->images[$file]['smask']);
    }
}

protected function _putimage(&$info)
{
    $this->_newobj();
    $info['n'] = $this->n;
    $this->_out('<</Type /XObject');
    $this->_out('/Subtype /Image');
    $this->_out('/Width '.$info['w']);
    $this->_out('/Height '.$info['h']);
    if($info['cs']=='Indexed')
        $this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
    else
    {
        $this->_out('/ColorSpace /'.$info['cs']);
        if($info['cs']=='DeviceCMYK')
            $this->_out('/Decode [1 0 1 0 1 0 1 0]');
    }
    $this->_out('/BitsPerComponent '.$info['bpc']);
    if(isset($info['f']))
        $this->_out('/Filter /'.$info['f']);
    if(isset($info['dp']))
        $this->_out('/DecodeParms <<'.$info['dp'].'>>');
    if(isset($info['trns']) && is_array($info['trns']))
    {
        $trns = '';
        for($i=0;$i<count($info['trns']);$i++)
            $trns .= $info['trns'][$i].' '.$info['trns'][$i].' ';
        $this->_out('/Mask ['.$trns.']');
    }
    if(isset($info['smask']))
        $this->_out('/SMask '.($this->n+1).' 0 R');
    $this->_out('/Length '.strlen($info['data']).'>>');
    $this->_putstream($info['data']);
    $this->_out('endobj');
    // Soft mask
    if(isset($info['smask']))
    {
        $dp = '/Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns '.$info['w'];
        $smask = array('w'=>$info['w'], 'h'=>$info['h'], 'cs'=>'DeviceGray', 'bpc'=>8, 'f'=>$info['f'], 'dp'=>$dp, 'data'=>$info['smask']);
        $this->_putimage($smask);
    }
    // Palette
    if($info['cs']=='Indexed')
    {
        $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
        $pal = ($this->compress) ? gzcompress($info['pal']) : $info['pal'];
        $this->_newobj();
        $this->_out('<<'.$filter.'/Length '.strlen($pal).'>>');
        $this->_putstream($pal);
        $this->_out('endobj');
    }
}

protected function _putxobjectdict()
{
    foreach($this->images as $image)
        $this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
}

protected function _putresourcedict()
{
    $this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
    $this->_out('/Font <<');
    foreach($this->fonts as $font)
        $this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
    $this->_out('>>');
    $this->_out('/XObject <<');
    $this->_putxobjectdict();
    $this->_out('>>');
}

protected function _putresources()
{
    $this->_putfonts();
    $this->_putimages();
    // Resource dictionary
    $this->_newobj(2);
    $this->_out('<<');
    $this->_putresourcedict();
    $this->_out('>>');
    $this->_out('endobj');
}
}