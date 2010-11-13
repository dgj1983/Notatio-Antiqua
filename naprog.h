/*-----------------------------------------------------------------------------
|  This file is part of Notatio Antiqua (c) 2009-2010 David Gippner           |
-------------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; version 3 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

-----------------------------------------------------------------------------*/

#ifndef NAPROG_H
#define NAPROG_H

#include <QMainWindow>
#include <QStyle>
#include <QDesktopServices>
#include <QDirIterator>
#include <QProcess>
#include <QSettings>
#include "nasyntax.h"
#include "nasettings.h"
#include "nahelp.h"
#include "namdi.h"
#include "naclefselect.h"
#include "naheaderwizard.h"

class MdiChild;
QT_BEGIN_NAMESPACE
class QAction;
class QMenu;
class QMdiArea;
class QMdiSubWindow;
class QSignalMapper;
QT_END_NAMESPACE


namespace Ui {
    class NaProg;
}

struct QHeaderInfo
{
    QString name;
    QString arranger;
    QString officepart;
    QString mode;
    QString annotation1;
    QString annotation2;
    QString commentary;
    QString font;
    QString gabccopy;
    QString scorecopy;
    QString occasion;
    QString meter;
    QString author;
    QString date;
    QString manuscript;
    QString mreference;
    QString book;
    QString transcriber;
    QString tdate;
    QString instyle;
    QString notes;
};

class NaProg : public QMainWindow {
    Q_OBJECT
public:
    NaProg(QWidget *parent = 0);
    ~NaProg();
    QHeaderInfo hi;   
    QProcess * execProg;
    QString LaTeX_Path;
    QString Lilypond_Path;
    QString Gregorio_Path;
    QString tmplName;


protected:
    void changeEvent(QEvent *e);

private slots:
    void on_actionClef_triggered();
    void on_actionInitialisierung_triggered();
    void on_action_ber_triggered();
    void on_actionAufr_umen_triggered();
    void on_actionEinstellungen_2_triggered();
    void on_actionPDF_ansehen_triggered();
    void on_actionGregorioTeX_PDF_triggered();
    void on_actionLyTeX_PDF_triggered();
    void on_actionLaTeX_PDF_triggered();
    void on_actionSpeichern_unter_triggered();
    void on_actionSpeichern_triggered();
    void on_action_ffnen_triggered();
    void on_actionNeu_triggered();
    void on_actionEinf_gen_triggered();
    void on_actionAusschneiden_triggered();
    void on_actionKopieren_triggered();
    void on_action_ber_2_triggered();
    void neu();
    void offen();
    void offenLetzte();
    void offenWieder();
    void speichern();
    void speichernunter();
    void ausschneiden();
    void kopieren();
    void einfuegen();
    void updateMenus();
    void ausfuehren(QString program, QStringList arguments, QString work_path);
    MdiChild *createMdiChild();
    void setActiveSubWindow(QWidget *window);
    void gregorio_extract();
    void gregorio_prepare();
    void reset_headers();
    void tolog();
    void on_actionHeader_Wizard_triggered();

    void on_actionCeleriter_triggered();

    void on_actionTenere_triggered();

    void on_actionMediocriter_triggered();

    void on_actionNon_triggered();

    void on_actionExspecta_triggered();

    void on_actionVirgula_triggered();

    void on_actionQuarter_Bar_triggered();

    void on_actionHalf_Bar_triggered();

    void on_actionDouble_Bar_triggered();

    void on_actionFull_Bar_triggered();

    void on_actionDagger_triggered();

    void on_actionCross_triggered();

    void on_actionVerse_triggered();

    void on_actionResponse_triggered();

    void on_actionAntiphon_triggered();

    void on_actionAccented_triggered();

private:
    void info();
    void createActions();
    void createMenus();
    void createWorkspaces();
    void createStatusBar();
    void readSettings();
    void writeSettings();
    void error_noopenfile();
    void initialization();
    MdiChild *activeMdiChild();
    QMdiSubWindow *findMdiChild(const QString &fileName);
    Ui::NaProg *ui;
    QToolBar *fileToolBar;
    QToolBar *editToolBar;
    // QMdiArea *mdiArea;
    QDockWidget *nadock;
    QTextEdit *logWindow;
    QSignalMapper *windowMapper;
    QAction *toolneu;
    QAction *tooloffen;
    QAction *toolspeichern;
    QAction *toolausschneiden;
    QAction *tooleinfuegen;
    QAction *toolkopieren;
    QAction *lastOpenFile;
};

#endif // NAPROG_H
