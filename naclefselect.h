#ifndef NACLEFSELECT_H
#define NACLEFSELECT_H

#include <QDialog>
namespace Ui {
    class NAClefSelect;
}

class NAClefSelect : public QDialog
{
    Q_OBJECT

public:
    explicit NAClefSelect(QWidget *parent = 0);
    ~NAClefSelect();
    QString clefS;

private:
    Ui::NAClefSelect *ui;

private slots:
    void on_NAClefSelect_accepted();
};

#endif // NACLEFSELECT_H
