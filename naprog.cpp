/*-----------------------------------------------------------------------------
|  This file is part of Notatio Antiqua (c) 2009-2010 David Gippner           |
-------------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software Foundation;
version 3 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

-----------------------------------------------------------------------------*/

#include <QtGui>
#include "naprog.h"
#include "ui_naprog.h"



NaProg::NaProg(QWidget *parent) :
    QMainWindow(parent),
    ui(new Ui::NaProg)
{
    ui->setupUi(this);
    createWorkspaces();
}


NaProg::~NaProg()
{
    delete ui;
}

void NaProg::ausfuehren(QString program, QStringList arguments, QString work_path)
{
#if defined (Q_WS_MAC) | defined (Q_WS_X11)
    QString mydelim = ":";
#endif
#if defined (Q_WS_WIN) | defined (__MINGW32__)
    QString mydelim = ";";
#endif
    execProg = new QProcess(this);
    QStringList environment = QProcess::systemEnvironment();
    environment.replaceInStrings(QRegExp("^PATH=(.*)", Qt::CaseInsensitive), "PATH=\\1"+mydelim+LaTeX_Path+mydelim+Lilypond_Path+mydelim+Gregorio_Path);
    execProg->setEnvironment(QProcess::systemEnvironment());
    execProg->setWorkingDirectory(work_path);
    execProg->setProcessChannelMode(QProcess::MergedChannels);
    connect(execProg,SIGNAL(readyRead()),this,SLOT(tolog()));
    execProg->start(program,arguments);
}

void NaProg::changeEvent(QEvent *e)
{
    QMainWindow::changeEvent(e);
    switch (e->type()) {
    case QEvent::LanguageChange:
        ui->retranslateUi(this);
        break;
    default:
        break;
    }
}

void NaProg::info()
{
    QMessageBox::about(this, tr("Copyright-Informationen"),
             tr("<h1>Notatio Antiqua 0.7&szlig;</h1>"
                "&copy; 2009-2010 DGSOFTWARE<br /><br />"
                "David Gippner M.A.<br />"
                "Hans-Berger-Stra&szlig;e 20<br />"
                "07747 Jena<br />"
                "<a href='mailto:davidgippner@googlemail.com'>davidgippner@googlemail.com</a><br /><br />"
                "This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.<br /> "
                "This program is distributed in the hope that it will be useful, "
                    "but WITHOUT ANY WARRANTY; without even the implied warranty of "
                    "MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the "
                    "GNU General Public License for more details. "
                    "You should have received a copy of the GNU General Public License "
                    "along with this program.  If not, see <a href=http://www.gnu.org/licenses/>http://www.gnu.org/licenses/</a>."));
}


void NaProg::on_action_ber_2_triggered()
{
    info();
}

void NaProg::neu()
{
    MdiChild *child = createMdiChild();
    child->newFile();
    child->show();
}

void NaProg::offen()
{
    QString fileName = QFileDialog::getOpenFileName(this,
                                                    tr("Open File"),"",
                                                    "LyTeX Lilypond and LaTeX (*.lytex);;"
                                                    "LaTeX Document (*.tex *.ltx);;"
                                                    "Gregorio GABC (*.gabc)");
    if (!fileName.isEmpty()) {
        QMdiSubWindow *existing = findMdiChild(fileName);
        if (existing) {
            ui->mdiArea->setActiveSubWindow(existing);
            return;
        }

        MdiChild *child = createMdiChild();
        if (child->loadFile(fileName)) {
            statusBar()->showMessage(tr("File opened."), 2000);
            child->show();
        } else {
            child->close();
        }
    }
}

void NaProg::offenLetzte()
{
    QSettings* preferences = new QSettings(QSettings::IniFormat, QSettings::UserScope,
    "DGSOFTWARE", "Notatio Antiqua");
    preferences->beginGroup("File");
    QString fileName = preferences->value("lastOpen").toString();
    if (!fileName.isEmpty()) {
        QMdiSubWindow *existing = findMdiChild(fileName);
        if (existing) {
            ui->mdiArea->setActiveSubWindow(existing);
            return;
        }

        MdiChild *child = createMdiChild();
        if (child->loadFile(fileName)) {
            statusBar()->showMessage(tr("File opened."), 2000);
            child->show();
        } else {
            child->close();
        }
    }
}

void NaProg::offenWieder()
{
    if (activeMdiChild()) {
    QString fileName = QFileInfo(activeMdiChild()->curFile).absoluteFilePath();
    QMessageBox::StandardButton quest;
    quest = QMessageBox::question(this, tr("Notatio Antiqua"),
                 tr("'%1' will be reopened.\n"
                    "Do you really want to revert it to the last saved state?")
                 .arg(fileName),
                 QMessageBox::Yes | QMessageBox::No);
    if (quest == QMessageBox::Yes)
    {
        if (!fileName.isEmpty()) {
            QMdiSubWindow *existing = findMdiChild(fileName);
            if (existing) {
                ui->mdiArea->setActiveSubWindow(existing);
                return;
            }

            MdiChild *child = createMdiChild();
            if (child->loadFile(fileName)) {
                statusBar()->showMessage(tr("File reopened."), 2000);
                child->show();
            } else {
                child->close();
            }
        }
    }
    else if (quest == QMessageBox::No)
        return;
}
}

void NaProg::speichern()
{
    if (activeMdiChild() && activeMdiChild()->save())
        statusBar()->showMessage(tr("Saved."), 2000);
}

void NaProg::speichernunter()
{
    if (activeMdiChild() && activeMdiChild()->saveAs())
        statusBar()->showMessage(tr("Saved."), 2000);
}

void NaProg::ausschneiden()
{
    if (activeMdiChild())
        activeMdiChild()->cut();
}

void NaProg::kopieren()
{
    if (activeMdiChild())
        activeMdiChild()->copy();
}

void NaProg::einfuegen()
{
    if (activeMdiChild())
        activeMdiChild()->paste();
}



MdiChild *NaProg::createMdiChild()
{
    MdiChild *child = new MdiChild;
    ui->mdiArea->addSubWindow(child);
    return child;
}

void NaProg::on_actionKopieren_triggered()
{
    kopieren();
}

void NaProg::on_actionAusschneiden_triggered()
{
    ausschneiden();
}

void NaProg::on_actionEinf_gen_triggered()
{
    einfuegen();
}

MdiChild *NaProg::activeMdiChild()
{
    if (QMdiSubWindow *activeSubWindow = ui->mdiArea->activeSubWindow())
        return qobject_cast<MdiChild *>(activeSubWindow->widget());
    return 0;
}

QMdiSubWindow *NaProg::findMdiChild(const QString &fileName)
{
    QString canonicalFilePath = QFileInfo(fileName).canonicalFilePath();

    foreach (QMdiSubWindow *window, ui->mdiArea->subWindowList()) {
        MdiChild *mdiChild = qobject_cast<MdiChild *>(window->widget());
        if (mdiChild->currentFile() == canonicalFilePath)
            return window;
    }
    return 0;
}

void NaProg::setActiveSubWindow(QWidget *window)
{
    if (!window)
        return;
    ui->mdiArea->setActiveSubWindow(qobject_cast<QMdiSubWindow *>(window));
}

void NaProg::on_actionNeu_triggered()
{
    neu();
}

void NaProg::on_action_ffnen_triggered()
{
    offen();
}

void NaProg::on_actionSpeichern_triggered()
{
    speichern();
}

void NaProg::on_actionSpeichern_unter_triggered()
{
    speichernunter();
}

void NaProg::updateMenus()
{

}

void NaProg::createWorkspaces()
{
    connect(ui->actionBeenden, SIGNAL(triggered()), qApp, SLOT(closeAllWindows()));
    setCentralWidget(ui->mdiArea);
    nadock = new QDockWidget;
    logWindow = new QTextEdit;
    logWindow->setReadOnly(true);
    nadock->setWindowTitle(tr("Program output"));
    nadock->setAllowedAreas(Qt::BottomDockWidgetArea);
    nadock->setFeatures(QDockWidget::NoDockWidgetFeatures);
    nadock->setWidget(logWindow);
    addDockWidget(Qt::BottomDockWidgetArea, nadock);
    connect(ui->mdiArea, SIGNAL(subWindowActivated(QMdiSubWindow *)),
            this, SLOT(updateMenus()));
    windowMapper = new QSignalMapper(this);
    connect(windowMapper, SIGNAL(mapped(QWidget *)),
            this, SLOT(setActiveSubWindow(QWidget *)));
    setWindowTitle(tr("Notatio Antiqua 0.7 beta"));
    setUnifiedTitleAndToolBarOnMac(true);
    setStatusBar(statusBar());
    QSettings* preferences = new QSettings(QSettings::IniFormat, QSettings::UserScope,
    "DGSOFTWARE", "Notatio Antiqua");
    QFileInfo areprefsexisting(preferences->fileName());
    if (!areprefsexisting.exists()) initialization();
    preferences->beginGroup("Paths");
    LaTeX_Path = preferences->value("latexPath").toString();
    Lilypond_Path = preferences->value("lilypondPath").toString();
    Gregorio_Path = preferences->value("gregorioPath").toString();
    preferences->endGroup();
    preferences->beginGroup("File");
    ui->menuDatei->addSeparator();
    lastOpenFile = new QAction(QFileInfo(preferences->value("lastOpen").toString()).fileName(),this);
    preferences->endGroup();
    lastOpenFile->setVisible(true);
    connect(lastOpenFile, SIGNAL(triggered()),
            this, SLOT(offenLetzte()));
    connect(ui->actionErneut_ffnen,SIGNAL(triggered()),this,SLOT(offenWieder()));
    ui->menuDatei->addAction(lastOpenFile);

}

void NaProg::on_actionLaTeX_PDF_triggered()
{
 if (activeMdiChild()) {
 QStringList mitzugeben;
 QFileInfo openfile(activeMdiChild()->curFile);
 QString workingpath = openfile.absolutePath();
 mitzugeben << "--interaction=nonstopmode" << activeMdiChild()->curFile;
 ausfuehren(LaTeX_Path+"/pdflatex", mitzugeben,workingpath);
}
 else error_noopenfile();
}

void NaProg::on_actionLyTeX_PDF_triggered()
{
    if (activeMdiChild()) {
    QFileInfo openfile(activeMdiChild()->curFile);
    QString workingpath = openfile.absolutePath();
    QStringList mitzugeben;
    mitzugeben << "--pdf --latex-program=luashell" << activeMdiChild()->curFile;
    ausfuehren(Lilypond_Path+"/lilypond-book",mitzugeben,workingpath);
    }
    else error_noopenfile();
}

void NaProg::on_actionGregorioTeX_PDF_triggered()
{
  if (activeMdiChild())
    {
    gregorio_extract();
    QStringList mitzugeben;
    QFileInfo openfile(activeMdiChild()->curFile);
    QString workingpath = openfile.absolutePath();
    mitzugeben << "-interaction=nonstopmode" <<  "--shell-escape" << workingpath+"/"+openfile.baseName()+"-main.tex";
    ausfuehren(LaTeX_Path+"/lualatex",mitzugeben,workingpath);
}
 else error_noopenfile();

}

void NaProg::on_actionPDF_ansehen_triggered()
{
    if (activeMdiChild())
    {
      QStringList mitzugeben;
      QFileInfo openfile(activeMdiChild()->curFile); // extracting file name
      QString workingpath = openfile.absolutePath(); // strip closing /
      if (openfile.completeSuffix() == "gabc")
        QDesktopServices::openUrl(QUrl("file://"+workingpath+"/"+openfile.baseName()+"-main.pdf", QUrl::TolerantMode));
      else
        QDesktopServices::openUrl(QUrl("file://"+workingpath+"/"+openfile.baseName()+".pdf", QUrl::TolerantMode));
  }
    else error_noopenfile();
}

void NaProg::on_actionEinstellungen_2_triggered()
{
    NASettings* settingsdlg = new NASettings;
  settingsdlg->setModal(true);
  settingsdlg->show();
}

void NaProg::on_actionAufr_umen_triggered()
{
 if (activeMdiChild())
    {
       QFileInfo openfile(activeMdiChild()->curFile);
       QString workingpath = openfile.absolutePath();
       QStringList garbage;
       garbage << ".dep" << ".aux" << ".log" <<  ".bbl" <<  ".ilg" << ".sav"
               << ".bak" << "-blx.bib" << ".out" << ".toc" << "*.blg" << ".ind" << ".idx"
               << ".gaux" << "~" << ".backup" << "tmp" << "snippet" << "-auto.tex";
       QDirIterator garbage_collector(workingpath,QDir::Files,QDirIterator::NoIteratorFlags);
       while (garbage_collector.hasNext())
       {
           garbage_collector.next();
           foreach (QString str, garbage)
           {
               if (garbage_collector.fileInfo().fileName().contains(str))
               {
                QDir::setCurrent(workingpath);
                QFile *removefile = new QFile(garbage_collector.fileInfo().canonicalFilePath());
                logWindow->append("Cleaning up "+garbage_collector.fileInfo().fileName());
                removefile->remove();
               }
           }

       }
   }

  else error_noopenfile();

}

void NaProg::gregorio_extract()
{
    QString file_content;
    QTextStream gabc_content(&file_content);
    QStringList gregorio_header;
    gabc_content << activeMdiChild()->toPlainText();
    int info_count = 0;
    while (!gabc_content.atEnd()) // read header until %%
    {
        gregorio_header << gabc_content.readLine();
        if (gregorio_header[info_count] == "%%" || gregorio_header[info_count].isEmpty()) break;
        ++info_count;
     }
    if (!gregorio_header.isEmpty())
    {
        gregorio_header.removeLast(); // %% is removed from StringList
        foreach (QString element, gregorio_header) // filling variable with header information
        {
         element.resize(element.size() - 1);
         if (element.contains("name:"))
             hi.name = element.section(":",1,1).simplified();

         if (element.contains("arranger:"))
             hi.arranger = element.section(":",1,1).simplified();

         if (element.contains("office-part:"))
             hi.officepart = element.section(":",1,1).simplified();

         if (element.contains("mode:"))
              hi.mode = element.section(":",1,1).simplified();

         if (element.contains("annotation:"))
         {
              if (hi.annotation1.isEmpty()) hi.annotation1 = element.section(":",1,1).simplified();
              else hi.annotation2 = element.section(":",1,1).simplified();
         }

         if (element.contains("commentary:"))
              hi.commentary = element.section(":",1,1).simplified();

         if (element.contains("gregoriotex-font:"))
              hi.font = element.section(":",1,1).simplified();

         if (element.contains("gabc-copyright:"))
              hi.gabccopy = element.section(":",1,1).simplified();

         if (element.contains("score-copyright:"))
              hi.scorecopy = element.section(":",1,1).simplified();

         if (element.contains("occasion:"))
              hi.occasion = element.section(":",1,1).simplified();

         if (element.contains("meter:"))
              hi.meter = element.section(":",1,1).simplified();

         if (element.contains("author:"))
              hi.author = element.section(":",1,1).simplified();

         if (element.contains("date:"))
              hi.date = element.section(":",1,1).simplified();

         if (element.contains("manuscript:"))
              hi.manuscript = element.section(":",1,1).simplified();

         if (element.contains("manuscript-reference:"))
              hi.mreference = element.section(":",1,1).simplified();

         if (element.contains("book:"))
              hi.book = element.section(":",1,1).simplified();

         if (element.contains("transcriber:"))
              hi.transcriber = element.section(":",1,1).simplified();

         if (element.contains("transcription-date:"))
              hi.tdate = element.section(":",1,1).simplified();

         if (element.contains("initial-style:"))
             hi.instyle = element.section(":",1,1).simplified();

         if (element.contains("user-notes:"))
              hi.notes = element.section(":",1,1).simplified();
        }
    }
gregorio_prepare();
}

void NaProg::gregorio_prepare()
{
  QFileInfo openfile(activeMdiChild()->curFile);
  QMessageBox::question(this,tr("Notatio Antiqua"),tr("Please select a template for the creation of the PDF file."),QMessageBox::Ok);
  QString tmplName = QFileDialog::getOpenFileName(this,tr("Open Template"),"",
                                                  "Notatio Antiqua Template (*.natemplate)");
  QString gabcName = openfile.fileName();
  QString outputName = openfile.absolutePath()+"/"+openfile.baseName()+"-main.tex";
  QFile tmpl(tmplName);
  QFile output(outputName);
  QStringList tmplcontent;
  tmpl.open(QFile::ReadOnly | QFile::Text);
  QTextStream in(&tmpl);
  int i = 0;
  while (!in.atEnd())
  {
      tmplcontent << in.readLine();
      ++i;
  }
  if (hi.font == "" ) hi.font = "greciliae"; // Standard
  tmplcontent.replaceInStrings(QRegExp("VAR:FONT"), hi.font);
  tmplcontent.replaceInStrings(QRegExp("VAR:NAME"), hi.name);
  tmplcontent.replaceInStrings(QRegExp("VAR:ANN1"), hi.annotation1);
  tmplcontent.replaceInStrings(QRegExp("VAR:ANN2"), hi.annotation2);
  tmplcontent.replaceInStrings(QRegExp("VAR:COMMENTARY"), hi.commentary);
  tmplcontent.replaceInStrings(QRegExp("VAR:FILENAME"), gabcName);
  tmplcontent.replaceInStrings(QRegExp("VAR:USERNOTES"),hi.notes);
  i = 0;
  if (QFile::exists(outputName))
  {
      QString bak = outputName;
      bak.append("~");
      QFile::copy(outputName,bak);
      QFile::remove(outputName);
  }
  output.open(QFile::ReadWrite | QFile::Text);
  QTextStream out(&output);
      while (i <= tmplcontent.count()-1)
      {
          out << tmplcontent[i] << "\n";
          ++i;
      }
  reset_headers();
}

void NaProg::reset_headers()
{
    hi.name="";
    hi.font="";
    hi.annotation1="";
    hi.annotation2="";
    hi.arranger="";
    hi.author="";
    hi.book="";
    hi.commentary="";
    hi.date="";
    hi.gabccopy="";
    hi.instyle="";
    hi.manuscript="";
    hi.meter="";
    hi.mode="";
    hi.mreference="";
    hi.notes="";
    hi.occasion="";
    hi.officepart="";
    hi.scorecopy="";
    hi.tdate="";
    hi.transcriber="";
}

void NaProg::tolog()
{
        QByteArray raw_data = execProg->readAllStandardOutput();
        logWindow->append(QString(raw_data));
}

void NaProg::on_action_ber_triggered()
{
  NAHelp* helpwindow = new NAHelp;
  helpwindow->exec();
}

void NaProg::error_noopenfile()
{
    QMessageBox::critical(this,tr("Notatio Antiqua"),
                          tr("No document window open, you can't run the desired action."),QMessageBox::Ok,QMessageBox::NoButton);
}

void NaProg::initialization()
{
    QMessageBox::information(this,tr("Notatio Antiqua"),
                          tr("Initializing the paths for LaTeX, Lilypond and Gregorio. This can take a while, please be patient."),QMessageBox::Ok,QMessageBox::NoButton);
    LaTeX_Path = ""; // Initialisierung der Variablen
    Lilypond_Path = "";
    Gregorio_Path = "";
#ifdef Q_WS_X11
    QDirIterator where_are_you("/usr",QDir::Files,QDirIterator::Subdirectories);
    while (where_are_you.hasNext())
    {
        where_are_you.next();
        logWindow->append("Searching in:"+where_are_you.fileInfo().canonicalPath());
        if (where_are_you.fileInfo().fileName() == "lualatex")
        {
            if (LaTeX_Path.isEmpty())
                LaTeX_Path = where_are_you.fileInfo().canonicalPath();
            else if (QMessageBox::question(this,tr("Notatio Antiqua"),
                                           tr("More than one path for LaTeX has been found:\n"
                                              "already scanned: %1\n"
                                              "newly discovered: %2\n"
                                              "Do you want to use the newly discovered path instead?")
                .arg(LaTeX_Path)
                .arg(where_are_you.fileInfo().canonicalPath()),
                QMessageBox::Yes|QMessageBox::No) == QMessageBox::Yes) LaTeX_Path = where_are_you.fileInfo().canonicalPath();
        }
        if (where_are_you.fileInfo().fileName() == "lilypond-book")
        {
            if (Lilypond_Path.isEmpty())
                Lilypond_Path = where_are_you.fileInfo().canonicalPath();
            else if (QMessageBox::question(this,tr("Notatio Antiqua"),
                                           tr("More than one path for LilyPond has been found:\n"
                                              "already scanned: %1\n"
                                              "newly discovered: %2\n"
                                              "Do you want to use the newly discovered path instead?")
                .arg(Lilypond_Path)
                .arg(where_are_you.fileInfo().canonicalPath()),
                QMessageBox::Yes|QMessageBox::No) == QMessageBox::Yes) Lilypond_Path = where_are_you.fileInfo().canonicalPath();
        }

        if (where_are_you.fileInfo().fileName() == "gregorio")
        {
            if (Gregorio_Path.isEmpty())
                Gregorio_Path = where_are_you.fileInfo().canonicalPath();
            else if (QMessageBox::question(this,tr("Notatio Antiqua"),
                                           tr("More than one path for Gregorio has been found:\n"
                                              "already scanned: %1\n"
                                              "newly discovered: %2\n"
                                              "Do you want to use the newly discovered path instead?")
                .arg(Gregorio_Path)
                .arg(where_are_you.fileInfo().canonicalPath()),
                QMessageBox::Yes|QMessageBox::No) == QMessageBox::Yes) Gregorio_Path = where_are_you.fileInfo().canonicalPath();
        }


    }
#endif
#ifdef Q_WS_MAC
        QDirIterator where_are_you("/usr",QDir::Files,QDirIterator::Subdirectories);
        while (where_are_you.hasNext())
        {
            where_are_you.next();
            logWindow->append("Searching in:"+where_are_you.fileInfo().canonicalPath());
            if (where_are_you.fileInfo().fileName() == "lualatex")
            {
                if (LaTeX_Path.isEmpty())
                    LaTeX_Path = where_are_you.fileInfo().canonicalPath();
                else if (QMessageBox::question(this,tr("Notatio Antiqua"),
                                               tr("More than one path for LaTeX has been found:\n"
                                                  "already scanned: %1\n"
                                                  "newly discovered: %2\n"
                                                  "Do you want to use the newly discovered path instead?")
                    .arg(LaTeX_Path)
                    .arg(where_are_you.fileInfo().canonicalPath()),
                    QMessageBox::Yes|QMessageBox::No) == QMessageBox::Yes) LaTeX_Path = where_are_you.fileInfo().canonicalPath();
            }
            if (where_are_you.fileInfo().fileName() == "lilypond-book")
            {
                if (Lilypond_Path.isEmpty())
                    Lilypond_Path = where_are_you.fileInfo().canonicalPath();
                else if (QMessageBox::question(this,tr("Notatio Antiqua"),
                                               tr("More than one path for LilyPond has been found:\n"
                                                  "already scanned: %1\n"
                                                  "newly discovered: %2\n"
                                                  "Do you want to use the newly discovered path instead?")
                    .arg(Lilypond_Path)
                    .arg(where_are_you.fileInfo().canonicalPath()),
                    QMessageBox::Yes|QMessageBox::No) == QMessageBox::Yes) Lilypond_Path = where_are_you.fileInfo().canonicalPath();
            }

            if (where_are_you.fileInfo().fileName() == "gregorio")
            {
                if (Gregorio_Path.isEmpty())
                    Gregorio_Path = where_are_you.fileInfo().canonicalPath();
                else if (QMessageBox::question(this,tr("Notatio Antiqua"),
                                               tr("More than one path for Gregorio has been found:\n"
                                                  "already scanned: %1\n"
                                                  "newly discovered: %2\n"
                                                  "Do you want to use the newly discovered path instead?")
                    .arg(Gregorio_Path)
                    .arg(where_are_you.fileInfo().canonicalPath()),
                    QMessageBox::Yes|QMessageBox::No) == QMessageBox::Yes) Gregorio_Path = where_are_you.fileInfo().canonicalPath();
            }


        }
#endif
#if defined Q_WS_WIN | defined __MINGW32__
    QDirIterator where_are_you("C:/",QDir::Files,QDirIterator::Subdirectories);
    while (where_are_you.hasNext())
    {
        where_are_you.next();
        logWindow->append("Searching in:"+where_are_you.fileInfo().canonicalPath());
        if (where_are_you.fileInfo().fileName() == "lualatex.exe")
        {
            if (LaTeX_Path.isEmpty())
                LaTeX_Path = where_are_you.fileInfo().canonicalPath();
            else if (QMessageBox::question(this,tr("Notatio Antiqua"),
                                           tr("More than one path for LaTeX has been found:\n"
                                              "already scanned: %1\n"
                                              "newly discovered: %2\n"
                                              "Do you want to use the newly discovered path instead?")
                .arg(LaTeX_Path)
                .arg(where_are_you.fileInfo().canonicalPath()),
                QMessageBox::Yes|QMessageBox::No) == QMessageBox::Yes) LaTeX_Path = where_are_you.fileInfo().canonicalPath();
        }
        if (where_are_you.fileInfo().fileName() == "lilypond-book.py")
        {
            if (Lilypond_Path.isEmpty())
                Lilypond_Path = where_are_you.fileInfo().canonicalPath();
            else if (QMessageBox::question(this,tr("Notatio Antiqua"),
                                           tr("More than one path for LilyPond has been found:\n"
                                              "already scanned: %1\n"
                                              "newly discovered: %2\n"
                                              "Do you want to use the newly discovered path instead?")
                .arg(Lilypond_Path)
                .arg(where_are_you.fileInfo().canonicalPath()),
                QMessageBox::Yes|QMessageBox::No) == QMessageBox::Yes) Lilypond_Path = where_are_you.fileInfo().canonicalPath();
        }

        if (where_are_you.fileInfo().fileName() == "gregorio.exe")
        {
            if (Gregorio_Path.isEmpty())
                Gregorio_Path = where_are_you.fileInfo().canonicalPath();
            else if (QMessageBox::question(this,tr("Notatio Antiqua"),
                                           tr("More than one path for Gregorio has been found:\n"
                                              "already scanned: %1\n"
                                              "newly discovered: %2\n"
                                              "Do you want to use the newly discovered path instead?")
                .arg(Gregorio_Path)
                .arg(where_are_you.fileInfo().canonicalPath()),
                QMessageBox::Yes|QMessageBox::No) == QMessageBox::Yes) Gregorio_Path = where_are_you.fileInfo().canonicalPath();
        }


    }
#endif
    QSettings* preferences = new QSettings(QSettings::IniFormat,QSettings::UserScope,"DGSOFTWARE", "Notatio Antiqua"); // Speichern der Ini-Datei
    preferences->beginGroup("Paths");
    preferences->setValue("latexPath",LaTeX_Path);
    preferences->setValue("gregorioPath",Gregorio_Path);
    preferences->setValue("lilypondPath",Lilypond_Path);
    preferences->endGroup();
    preferences->beginGroup("Font");
    QFont saveFont;
    saveFont.setFamily("Courier New");
    preferences->setValue("standardFont",saveFont.family());
    preferences->endGroup();
    QMessageBox::information(this,tr("Notatio Antiqua"),
                          tr("The following paths have been found and have been written to ini file:\n LuaLaTeX: %1\n Lilypond: %2\n Gregorio: %3.\n"
                             "If these paths do not meet your standard installations, you can alter them in Extras->Options or run this initialization again.")
                             .arg(LaTeX_Path)
                             .arg(Lilypond_Path)
                             .arg(Gregorio_Path));
}

void NaProg::on_actionInitialisierung_triggered()
{
    initialization();
}

void NaProg::on_actionClef_triggered()
{
    QString retclef;
    NAClefSelect clefchoice;
    if (activeMdiChild())
    {
      if( clefchoice.exec() == QDialog::Accepted )
        {
          retclef = clefchoice.clefS;
        }
    }
    else error_noopenfile();
    if (!retclef.isEmpty())
        if(activeMdiChild())
            activeMdiChild()->append(retclef);
}
